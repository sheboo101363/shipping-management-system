<?php
error_reporting(0);
ini_set('display_errors', 0);

include 'db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">معرف الشحنة غير موجود.</div></div>';
    exit;
}
$parcel_id = intval($_GET['id']);

// جلب تفاصيل الشحنة مع بيانات الوكيل والمندوب والمنطقة والمحافظة
$query = "
    SELECT 
        p.*, 
        a.company_name, 
        a.commission_type, 
        a.commission_value,
        c.name as courier_name, 
        c.phone as courier_phone, 
        c.email as courier_email,
        ar.name as area_name,
        g.name as gov_name,
        b1.name as from_branch_name,
        b2.name as to_branch_name
    FROM 
        parcels p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN couriers c ON p.courier_id = c.id
    LEFT JOIN areas ar ON p.recipient_area_id = ar.id
    LEFT JOIN governorates g ON p.recipient_governorate_id = g.id
    LEFT JOIN branches b1 ON p.from_branch_id = b1.id
    LEFT JOIN branches b2 ON p.to_branch_id = b2.id
    WHERE 
        p.id = $parcel_id
";
$parcel_qry = $conn->query($query);
if ($parcel_qry && $parcel_qry->num_rows > 0) {
    $parcel_data = $parcel_qry->fetch_assoc();
} else {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">الشحنة غير موجودة.</div></div>';
    exit;
}

// مصفوفة الحالات (مع مطابقة النظام الجديد)
$status_arr = array(
    0 => "تم قبول الطرد من المندوب",
    1 => "تم استلام الطرد",
    2 => "تم الشحن",
    3 => "قيد النقل",
    4 => "وصل للوجهة",
    5 => "خرج للتسليم",
    6 => "جاهز للاستلام",
    7 => "تم التسليم",
    8 => "تم الاستلام",
    9 => "محاولة تسليم غير ناجحة"
);
$current_status = isset($parcel_data['status']) ? $parcel_data['status'] : 0;

// جلب بيانات العميل
$customer = null;
if (!empty($parcel_data['sender_phone'])) {
    $customer_qry = $conn->query("SELECT * FROM customers WHERE phone = '".$conn->real_escape_string($parcel_data['sender_phone'])."' LIMIT 1");
    if ($customer_qry && $customer_qry->num_rows > 0) {
        $customer = $customer_qry->fetch_assoc();
    }
}

// جلب تاريخ الحالات للشحنة
$tracks = $conn->query("SELECT * FROM parcel_tracks WHERE parcel_id = $parcel_id ORDER BY date_created ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الشحنة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap">
    <style>
        body { font-family: 'Tajawal', Arial, sans-serif; direction: rtl; background-color: #f4f7f6; }
        .card { border-radius: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .card-header { background-color: #17a2b8; color: white; border-radius: 1rem 1rem 0 0; padding: 1.5rem; text-align: center; }
        .info-card { background-color: #ffffff; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .info-card h6 { font-weight: bold; color: #17a2b8; margin-bottom: 10px; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; }
        .info-card p { margin-bottom: 5px; }
        .info-card p strong { color: #343a40; }
        .status-badge { font-size: 1rem; padding: 0.5em 1em; border-radius: 1.5rem; }
        .timeline { list-style: none; padding:0; margin:0; }
        .timeline li { position: relative; padding: 0 0 16px 0; }
        .timeline li:last-child { padding-bottom:0; }
        .timeline .timeline-status { font-weight:bold; color:#127fa1; }
        .timeline .timeline-date { color:#776; font-size:.96em; }
        .branch-info { color: #157d9f; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0 text-center"><i class="fas fa-box-open me-2"></i> تفاصيل الشحنة #<?php echo htmlspecialchars($parcel_data['reference_number'] ?? ''); ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- حالة الشحنة -->
                <div class="col-12 mb-4">
                    <div class="info-card text-center">
                        <h6>حالة الشحنة الحالية</h6>
                        <span class="badge bg-info text-dark status-badge"><?php echo htmlspecialchars($status_arr[$current_status] ?? 'غير معروف'); ?></span>
                        <p class="mt-2 text-muted"><small>آخر تحديث: <?php echo htmlspecialchars($parcel_data['date_created'] ?? 'غير متاح'); ?></small></p>
                    </div>
                </div>

                <!-- معلومات الشحنة -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-box me-2"></i> معلومات الشحنة</h6>
                        <p><strong>قيمة الشحنة:</strong> <?php echo number_format($parcel_data['price'] ?? 0, 2); ?> جنيه</p>
                        <p><strong>نوع الشحنة:</strong> <?php echo ($parcel_data['type'] ?? '') == '1' ? 'توصيل' : 'استلام'; ?></p>
                        <p><strong>الفرع المرسل:</strong> <span class="branch-info"><?php echo htmlspecialchars($parcel_data['from_branch_name'] ?? 'غير محدد'); ?></span></p>
                        <p><strong>الفرع المستلم:</strong> <span class="branch-info"><?php echo htmlspecialchars($parcel_data['to_branch_name'] ?? 'غير محدد'); ?></span></p>
                        <p><strong>منطقة المستلم:</strong> <?php echo htmlspecialchars($parcel_data['area_name'] ?? ''); ?> (<?php echo htmlspecialchars($parcel_data['gov_name'] ?? ''); ?>)</p>
                        <p><strong>وزن الشحنة:</strong> <?php echo htmlspecialchars($parcel_data['weight'] ?? 'غير محدد'); ?></p>
                        <p><strong>ابعاد الشحنة:</strong> 
                            <?php
                            echo "الطول: " . htmlspecialchars($parcel_data['length'] ?? '-') . " | ";
                            echo "العرض: " . htmlspecialchars($parcel_data['width'] ?? '-') . " | ";
                            echo "الارتفاع: " . htmlspecialchars($parcel_data['height'] ?? '-');
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- بيانات المرسل -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-user-circle me-2"></i> معلومات المرسل</h6>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($parcel_data['sender_name'] ?? ''); ?></p>
                        <p><strong>العنوان:</strong> <?php echo htmlspecialchars($parcel_data['sender_address'] ?? ''); ?></p>
                        <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($parcel_data['sender_phone'] ?? ''); ?></p>
                        <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($parcel_data['sender_email'] ?? ''); ?></p>
                        <p><strong>المحافظة:</strong> <?php echo htmlspecialchars($parcel_data['sender_governorate_id'] ?? ''); ?></p>
                        <p><strong>المنطقة:</strong> <?php echo htmlspecialchars($parcel_data['sender_area_id'] ?? ''); ?></p>
                        <?php if ($customer): ?>
                            <hr>
                            <h6 class="mb-2"><i class="fa fa-user-tag me-1"></i> بيانات العميل</h6>
                            <p><strong>اسم العميل:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                            <p><strong>حالة العميل:</strong> <?php echo ($customer['status'] ? 'نشط' : 'موقوف'); ?></p>
                            <p><strong>تاريخ إضافة العميل:</strong> <?php echo htmlspecialchars($customer['date_created']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- بيانات المستلم -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-user-circle me-2"></i> معلومات المستلم</h6>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($parcel_data['recipient_name'] ?? ''); ?></p>
                        <p><strong>العنوان:</strong> <?php echo htmlspecialchars($parcel_data['recipient_address'] ?? ''); ?></p>
                        <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($parcel_data['recipient_phone'] ?? ''); ?></p>
                        <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($parcel_data['recipient_email'] ?? ''); ?></p>
                        <p><strong>المحافظة:</strong> <?php echo htmlspecialchars($parcel_data['recipient_governorate_id'] ?? ''); ?></p>
                        <p><strong>المنطقة:</strong> <?php echo htmlspecialchars($parcel_data['recipient_area_id'] ?? ''); ?></p>
                    </div>
                </div>

                <!-- بيانات المندوب -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-shipping-fast me-2"></i> معلومات المندوب</h6>
                        <p><strong>اسم المندوب:</strong> <?php echo htmlspecialchars($parcel_data['courier_name'] ?? 'غير محدد'); ?></p>
                        <p><strong>رقم هاتف المندوب:</strong> <?php echo htmlspecialchars($parcel_data['courier_phone'] ?? ''); ?></p>
                        <p><strong>بريد المندوب:</strong> <?php echo htmlspecialchars($parcel_data['courier_email'] ?? ''); ?></p>
                    </div>
                </div>

                <!-- بيانات الوكيل -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-user-tie me-2"></i> معلومات الوكيل</h6>
                        <p><strong>الشركة:</strong> <?php echo htmlspecialchars($parcel_data['company_name'] ?? 'لا يوجد وكيل'); ?></p>
                        <p><strong>العمولة:</strong> 
                            <?php if (($parcel_data['commission_type'] ?? '') == 'fixed'): ?>
                                <?php echo number_format($parcel_data['commission_value'] ?? 0, 2); ?> جنيه (مبلغ ثابت)
                            <?php elseif (($parcel_data['commission_type'] ?? '') == 'percent'): ?>
                                <?php echo number_format($parcel_data['commission_value'] ?? 0, 2); ?> % (نسبة مئوية)
                            <?php else: ?>
                                غير محدد
                            <?php endif; ?>
                        </p>
                        <?php if (isset($parcel_data['agent_id'])): ?>
                            <a href="index.php?page=agent_finance&id=<?php echo $parcel_data['agent_id']; ?>" class="btn btn-sm btn-outline-info w-100 mt-2">
                                <i class="fas fa-file-invoice-dollar me-2"></i> عرض كشف حساب الوكيل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- تاريخ الحالات للشحنة -->
            <div class="row">
                <div class="col-12">
                    <div class="info-card">
                        <h6><i class="fa fa-history me-2"></i> سجل حالة الشحنة</h6>
                        <?php if ($tracks && $tracks->num_rows > 0): ?>
                            <ul class="timeline">
                                <?php while ($track = $tracks->fetch_assoc()): ?>
                                    <li>
                                        <span class="timeline-status"><?php echo htmlspecialchars($status_arr[$track['status']] ?? $track['status']); ?></span>
                                        <span class="timeline-date"> - <?php echo htmlspecialchars($track['date_created']); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>لا يوجد تغيير حالة حتى الآن.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>