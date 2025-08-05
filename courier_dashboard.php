<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['courier_id'])) {
    header("Location: courier_login.php");
    exit;
}
$courier_id = $_SESSION['courier_id'];

// حالات الشحنات
$status_arr = array(
    "تم قبول الطرد من المندوب","تم التحصيل","تم الشحن","في الطريق",
    "وصلت للوجهة","خارج للتوصيل","جاهزة للاستلام","تم التوصيل",
    "تم الاستلام","محاولة تسليم غير ناجحة"
);

$status_colors = [
    0 => 'bg-danger text-white',     // جديد
    7 => 'bg-success text-white',    // تم التوصيل
    3 => 'bg-warning text-dark',     // في الطريق
    4 => 'bg-warning text-dark',
    5 => 'bg-warning text-dark',
    6 => 'bg-warning text-dark',
];

$status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "courier_id = $courier_id";
if ($status_filter >= 0) {
    $where .= " AND status = $status_filter";
}
if ($search != '') {
    $search_sql = $conn->real_escape_string($search);
    $where .= " AND (reference_number LIKE '%$search_sql%' OR recipient_name LIKE '%$search_sql%')";
}

// إحصائيات وعدد وقيمة الشحنات
$total = $conn->query("SELECT COUNT(*) as cnt FROM parcels WHERE courier_id = $courier_id")->fetch_assoc()['cnt'];
$delivered = $conn->query("SELECT COUNT(*) as cnt FROM parcels WHERE courier_id = $courier_id AND status = 7")->fetch_assoc()['cnt'];
$new = $conn->query("SELECT COUNT(*) as cnt FROM parcels WHERE courier_id = $courier_id AND status = 0")->fetch_assoc()['cnt'];
$total_value = $conn->query("SELECT SUM(price) AS total_price FROM parcels WHERE courier_id = $courier_id")->fetch_assoc()['total_price'];
$delivered_value = $conn->query("SELECT SUM(price) AS delivered_price FROM parcels WHERE courier_id = $courier_id AND status = 7")->fetch_assoc()['delivered_price'];

// جلب بيانات المندوب
$courier = $conn->query("SELECT c.*, b.branch_code FROM couriers c LEFT JOIN branches b ON c.branch_id = b.id WHERE c.id = $courier_id")->fetch_assoc();

// جلب الشحنات
$parcels = $conn->query("SELECT * FROM parcels WHERE $where ORDER BY date_created DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>لوحة المندوب - <?php echo htmlspecialchars($courier['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 RTL + FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6fb; }
        .card-box { border-radius: 22px; box-shadow: 0 2px 12px #e3e3e3; margin-bottom: 22px; background: #fff; }
        .stats-circle { width:70px; height:70px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.7rem; font-weight:bold; margin:0 auto 8px; box-shadow:0 2px 8px #ddd; }
        .parcel-card { border-radius: 18px; box-shadow: 0 1px 6px #ddd; margin-bottom: 16px; background:#fff; }
        .status-badge { font-size: 13px; padding: 7px 14px; border-radius: 12px; font-weight: bold;}
        .note-textarea { min-width: 120px; max-width: 220px; height:36px; font-size:14px; }
        .card-footer { background: #f8f9fa; }
        .profile-pic {width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #2196f3;}
        .modal-content { border-radius:18px; }
        .btn-logout { position: absolute; left: 18px; top: 20px; }
        .chat-box { background: #f8f9fa; border-radius:12px; padding:10px; margin-bottom:12px; max-height:180px; overflow-y:auto; border:1px solid #eee;}
        .chat-msg {margin-bottom:7px;}
        .chat-msg.courier {text-align:right;}
        .chat-msg.admin {text-align:left;}
        .chat-msg .msg-content {display:inline-block; padding:6px 16px; border-radius:12px;}
        .chat-msg.courier .msg-content {background:#e3f2fd;}
        .chat-msg.admin .msg-content {background:#ffe0b2;}
        .chat-date {font-size:12px;color:#888;display:block;}
        @media (max-width: 576px) {
            .stats-circle {width: 52px; height: 52px; font-size:1.1rem;}
            .profile-pic {width:44px; height:44px;}
        }
        .dark-mode {background: #222;}
        .dark-mode .card-box, .dark-mode .parcel-card, .dark-mode .modal-content {background: #1a1a1a; color: #fff;}
        .dark-mode .chat-box {background:#222;}
        .dark-mode .chat-msg.courier .msg-content {background:#1565c0;color:#fff;}
        .dark-mode .chat-msg.admin .msg-content {background:#ef6c00;color:#fff;}
    </style>
</head>
<body>
<div class="container py-2">

    <!-- ملف شخصي للمندوب -->
    <div class="card-box p-3 mb-3 position-relative">
        <div class="d-flex align-items-center gap-3">
            <img src="<?php echo !empty($courier['profile_pic'])? $courier['profile_pic'] : 'avatar.png'; ?>" class="profile-pic" alt="مندوب">
            <div>
                <div class="fw-bold fs-5 text-primary"><?php echo htmlspecialchars($courier['name']); ?></div>
                <div class="text-muted">فرع: <?php echo htmlspecialchars($courier['branch_code']); ?></div>
                <small class="text-secondary">رقم الجوال: <?php echo htmlspecialchars($courier['phone']); ?></small>
            </div>
        </div>
        <a href="courier_logout.php" class="btn btn-outline-danger btn-logout">خروج <i class="fa fa-sign-out"></i></a>
    </div>

    <!-- شريط الإحصائيات وعدد وقيمة الشحنات -->
    <div class="card-box p-3">
        <div class="row text-center mb-3">
            <div class="col-4">
                <div class="stats-circle bg-success text-white"><i class="fa fa-check"></i></div>
                <div class="fw-bold">تم التوصيل</div>
                <div class="fs-5"><?php echo $delivered; ?></div>
            </div>
            <div class="col-4">
                <div class="stats-circle bg-danger text-white"><i class="fa fa-star"></i></div>
                <div class="fw-bold">جديدة</div>
                <div class="fs-5"><?php echo $new; ?></div>
            </div>
            <div class="col-4">
                <div class="stats-circle bg-primary text-white"><i class="fa fa-cube"></i></div>
                <div class="fw-bold">كل الشحنات</div>
                <div class="fs-5"><?php echo $total; ?></div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-6">
                <div class="box-value">
                    <i class="fa fa-coins"></i> إجمالي قيمة كل الشحنات: 
                    <?php echo number_format($total_value ?? 0, 2); ?> جنيه
                </div>
            </div>
            <div class="col-6">
                <div class="box-value green">
                    <i class="fa fa-money-check-alt"></i> قيمة الشحنات المسلمة بنجاح: 
                    <?php echo number_format($delivered_value ?? 0, 2); ?> جنيه
                </div>
            </div>
        </div>
    </div>

    <!-- بحث وفلترة -->
    <div class="card-box p-3">
        <form method="get" class="d-flex gap-2 flex-wrap mb-1">
            <input type="text" name="search" class="form-control flex-fill" placeholder="بحث برقم الشحنة أو اسم المستلم" value="<?php echo htmlspecialchars($search); ?>" style="max-width:200px;">
            <select name="status" class="form-select" style="max-width:150px;">
                <option value="-1">كل الحالات</option>
                <?php foreach($status_arr as $k=>$v): ?>
                    <option value="<?php echo $k; ?>" <?php if($status_filter == $k) echo "selected"; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-info"><i class="fa fa-search"></i> بحث</button>
            <a href="courier_dashboard.php" class="btn btn-secondary"><i class="fa fa-sync"></i> تحديث</a>
            <button type="button" class="btn btn-dark" id="toggleDark"><i class="fa fa-moon"></i></button>
        </form>
    </div>

    <!-- الشحنات في بطاقات ملونة -->
    <div>
    <?php if($parcels->num_rows == 0): ?>
        <div class="alert alert-warning text-center mt-4">لا توجد شحنات حالياً.</div>
    <?php else: ?>
        <?php while($row = $parcels->fetch_assoc()): 
            $colorClass = isset($status_colors[$row['status']]) ? $status_colors[$row['status']] : 'bg-info text-white';
            // سجل الملاحظات والشات
            $notes = $conn->query("SELECT * FROM parcels_notes WHERE parcel_id = {$row['id']} ORDER BY created_at ASC");
        ?>
            <div class="parcel-card card <?php echo $colorClass; ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">#<?php echo $row['reference_number']; ?></span>
                    <span class="status-badge <?php echo $colorClass; ?>">
                        <?php echo $status_arr[$row['status']] ?? 'غير معروف'; ?>
                    </span>
                </div>
                <div class="card-body bg-white text-dark">
                    <div><i class="fa fa-user text-primary"></i> <b>المستلم:</b> <?php echo htmlspecialchars($row['recipient_name']); ?></div>
                    <div><i class="fa fa-phone text-success"></i> <b>الجوال:</b> <?php echo htmlspecialchars($row['recipient_phone']); ?></div>
                    <div><i class="fa fa-map-marker-alt text-danger"></i> <b>العنوان:</b> <?php echo htmlspecialchars($row['recipient_address']); ?></div>
                    <div><i class="fa fa-money-bill text-warning"></i> <b>القيمة:</b> <?php echo number_format($row['price'],2); ?> جنيه</div>
                    <div><i class="fa fa-calendar text-info"></i> <b>تاريخ الإضافة:</b> <?php echo date('Y-m-d',strtotime($row['date_created'])); ?></div>
                    <!-- سجل الملاحظات والدردشة -->
                    <div class="chat-box mb-2">
                        <?php if($notes->num_rows > 0): foreach($notes as $n): ?>
                        <div class="chat-msg <?php echo $n['sender_type']; ?>">
                            <div class="msg-content"><?php echo htmlspecialchars($n['note']); ?></div>
                            <span class="chat-date"><?php echo date('Y-m-d H:i',strtotime($n['created_at'])); ?> - <?php echo $n['sender_type']=='courier'?'مندوب':'إدارة'; ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <span class="text-muted">لا توجد ملاحظات بعد.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer parcel-actions bg-light">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $row['id']; ?>"><i class="fa fa-info-circle"></i> تفاصيل</button>
                    <form method="post" action="courier_dashboard.php" style="display:inline;">
                        <input type="hidden" name="parcel_id" value="<?php echo $row['id']; ?>">
                        <select name="new_status" class="form-select form-select-sm d-inline-block" style="width:130px;">
                            <?php foreach($status_arr as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo $row['status'] == $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="courier_note" class="form-control form-control-sm note-textarea d-inline-block" placeholder="ملاحظة أو رسالة..." value="">
                        <button class="btn btn-success btn-sm ms-2"><i class="fa fa-save"></i> تحديث الحالة/إرسال ملاحظة</button>
                    </form>
                </div>
            </div>

            <!-- Modal تفاصيل الشحنة -->
            <div class="modal fade" id="detailsModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $row['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel<?php echo $row['id']; ?>">تفاصيل الشحنة #<?php echo $row['reference_number']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                  </div>
                  <div class="modal-body">
                    <div><strong>اسم المرسل:</strong> <?php echo htmlspecialchars($row['sender_name']); ?></div>
                    <div><strong>جوال المرسل:</strong> <?php echo htmlspecialchars($row['sender_phone']); ?></div>
                    <div><strong>عنوان المرسل:</strong> <?php echo htmlspecialchars($row['sender_address']); ?></div>
                    <hr>
                    <div><strong>اسم المستلم:</strong> <?php echo htmlspecialchars($row['recipient_name']); ?></div>
                    <div><strong>جوال المستلم:</strong> <?php echo htmlspecialchars($row['recipient_phone']); ?></div>
                    <div><strong>عنوان المستلم:</strong> <?php echo htmlspecialchars($row['recipient_address']); ?></div>
                    <hr>
                    <div><strong>قيمة الشحنة:</strong> <?php echo number_format($row['price'],2); ?> جنيه</div>
                    <div><strong>تاريخ الإضافة:</strong> <?php echo date('Y-m-d',strtotime($row['date_created'])); ?></div>
                    <div><strong>الحالة الحالية:</strong> <span class="status-badge <?php echo $colorClass; ?>"><?php echo $status_arr[$row['status']] ?? 'غير معروف'; ?></span></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                  </div>
                </div>
              </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // زر الوضع الليلي
  document.getElementById('toggleDark').onclick = function(){
    document.body.classList.toggle('dark-mode');
  }
</script>
</body>
</html>

<?php
// تحديث حالة الشحنة + إضافة ملاحظة في سجل الملاحظات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parcel_id'])) {
    $parcel_id = intval($_POST['parcel_id']);
    $new_status = isset($_POST['new_status']) ? intval($_POST['new_status']) : null;
    $courier_note = isset($_POST['courier_note']) ? trim($_POST['courier_note']) : '';

    if($new_status !== null){
        $conn->query("UPDATE parcels SET status = $new_status WHERE id = $parcel_id AND courier_id = $courier_id");
    }
    // سجل الملاحظة (حتى لو فاضي لا تضيف)
    if($courier_note != ''){
        $note_sql = "INSERT INTO parcels_notes (parcel_id, courier_id, note, sender_type) VALUES ($parcel_id, $courier_id, '".$conn->real_escape_string($courier_note)."', 'courier')";
        $conn->query($note_sql);
    }
    echo "<script>location.href='courier_dashboard.php';</script>";
    exit;
}
?>