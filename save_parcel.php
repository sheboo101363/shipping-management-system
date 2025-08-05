<?php
include('db_connect.php');
$conn->set_charset("utf8mb4");

session_start();
if (!isset($_SESSION['login_id'])) {
    echo json_encode(['status' => 0, 'message' => "يجب تسجيل الدخول أولًا."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
    echo json_encode(['status' => 0, 'message' => "بيانات غير صالحة."]);
    exit;
}

// تنظيف وتأمين البيانات
$sender_name = $conn->real_escape_string($_POST['sender_name'] ?? '');
$sender_phone = $conn->real_escape_string($_POST['sender_phone'] ?? '');
$sender_email = $conn->real_escape_string($_POST['sender_email'] ?? '');
$sender_address = $conn->real_escape_string($_POST['sender_address'] ?? '');
$sender_governorate_id = intval($_POST['sender_governorate_id'] ?? 0);
$sender_area_id = intval($_POST['sender_area_id'] ?? 0);

$recipient_name = $conn->real_escape_string($_POST['recipient_name'] ?? '');
$recipient_phone = $conn->real_escape_string($_POST['recipient_phone'] ?? '');
$recipient_email = $conn->real_escape_string($_POST['recipient_email'] ?? '');
$recipient_address = $conn->real_escape_string($_POST['recipient_address'] ?? '');
$recipient_governorate_id = intval($_POST['recipient_governorate_id'] ?? 0);
$recipient_area_id = intval($_POST['recipient_area_id'] ?? 0);

$courier_id = isset($_POST['courier_id']) ? intval($_POST['courier_id']) : 0; // تأكد من استلام المندوب
$from_branch_id = intval($_POST['from_branch_id'] ?? 0);
$to_branch_id = intval($_POST['to_branch_id'] ?? 0);
$type = intval($_POST['type'] ?? 1);

$weights = $_POST['weight'] ?? [];
$lengths = $_POST['length'] ?? [];
$widths = $_POST['width'] ?? [];
$heights = $_POST['height'] ?? [];
$prices = $_POST['price'] ?? [];

$agent_id = intval($_POST['agent_id'] ?? 0);
$agent_direction = $conn->real_escape_string($_POST['agent_direction'] ?? '');

$status = 0; // قيد التجهيز

// القيم المالية (من النموذج)
$courier_commission = floatval($_POST['courier_commission'] ?? 0);
$agent_commission = floatval($_POST['agent_commission'] ?? 0);
$project_profit    = floatval($_POST['project_profit'] ?? 0);

// ==========================
// منطق ربط المندوب - لا توزيع تلقائي أبداً
// ==========================
if ($agent_id > 0 && $agent_direction === "delivered") {
    // سيتم تسليمها للوكيل: لا يوجد مندوب إطلاقًا
    $courier_id = 0;
}
// في باقي الحالات، استخدم القيمة المدخلة من المستخدم (يدوي فقط)
// ==========================

// تحقق من أن المندوب مطلوب (إجباري) إذا لم يكن هناك وكيل "delivered"
if (
    ($agent_id == 0 || ($agent_id > 0 && $agent_direction !== "delivered"))
    && $courier_id == 0
) {
    echo json_encode(['status' => 0, 'message' => "يجب اختيار المندوب."]);
    exit;
}

$success_count = 0;
$error_messages = [];

$conn->begin_transaction();

try {
    $query = "INSERT INTO parcels (
        sender_name, sender_phone, sender_email, sender_address, sender_governorate_id, sender_area_id,
        recipient_name, recipient_phone, recipient_email, recipient_address, recipient_governorate_id, recipient_area_id,
        courier_id, from_branch_id, to_branch_id, type, weight, length, width, height, price,
        agent_id, agent_direction, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        throw new Exception("حدث خطأ في تحضير الاستعلام: " . $conn->error);
    }

    // ربط المتغيرات
    $stmt->bind_param("ssssiissiissiiiiiisssi",
        $sender_name, $sender_phone, $sender_email, $sender_address, $sender_governorate_id, $sender_area_id,
        $recipient_name, $recipient_phone, $recipient_email, $recipient_address, $recipient_governorate_id, $recipient_area_id,
        $courier_id, $from_branch_id, $to_branch_id, $type, $item_weight, $item_length, $item_width, $item_height, $item_price,
        $agent_id, $agent_direction, $status
    );

    foreach ($weights as $key => $item_weight) {
        if (isset($lengths[$key], $widths[$key], $heights[$key], $prices[$key])) {
            $item_weight = floatval($item_weight);
            $item_length = floatval($lengths[$key]);
            $item_width  = floatval($widths[$key]);
            $item_height = floatval($heights[$key]);
            $item_price  = floatval($prices[$key]);

            if ($stmt->execute()) {
                $parcel_id = $conn->insert_id;
                $success_count++;

                // تسجيل الحركات المالية تلقائياً
                // 1. إدخال رصيد من العميل (دخول)
                $conn->query("INSERT INTO cashbox_transactions 
                    (branch_id, type, relation_id, amount, direction, notes, created_at)
                    VALUES (
                        $from_branch_id, 'customer', 0, $item_price, 'in', 'إيداع من شحنة جديدة رقم $parcel_id', NOW()
                    )");

                // 2. خروج للوكيل (إن وجد)
                if ($agent_id > 0 && $agent_commission > 0) {
                    $conn->query("INSERT INTO cashbox_transactions 
                        (branch_id, type, relation_id, amount, direction, notes, created_at)
                        VALUES (
                            $from_branch_id, 'agent', $agent_id, $agent_commission, 'out', 'صرف عمولة وكيل للشحنة رقم $parcel_id', NOW()
                        )");
                }

                // 3. خروج للمندوب (فقط إذا كان courier_id أكبر من صفر)
                if ($courier_id > 0 && $courier_commission > 0) {
                    $conn->query("INSERT INTO cashbox_transactions 
                        (branch_id, type, relation_id, amount, direction, notes, created_at)
                        VALUES (
                            $from_branch_id, 'courier', $courier_id, $courier_commission, 'out', 'صرف عمولة مندوب للشحنة رقم $parcel_id', NOW()
                        )");
                }

                // 4. ربح المشروع
                if ($project_profit > 0) {
                    $conn->query("INSERT INTO cashbox_transactions 
                        (branch_id, type, relation_id, amount, direction, notes, created_at)
                        VALUES (
                            $from_branch_id, 'project', 0, $project_profit, 'in', 'ربح المشروع من شحنة رقم $parcel_id', NOW()
                        )");
                }

            } else {
                $error_messages[] = "خطأ أثناء حفظ الشحنة رقم " . ($key + 1) . ": " . $stmt->error;
            }
        }
    }

    if ($success_count > 0 && count($error_messages) == 0) {
        $conn->commit();
        echo json_encode(['status' => 1, 'message' => "تم حفظ $success_count شحنة بنجاح."]);
    } else {
        $conn->rollback();
        echo json_encode(['status' => 0, 'message' => implode(" | ", $error_messages)]);
    }

    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 0, 'message' => $e->getMessage()]);
}

$conn->close();
?>