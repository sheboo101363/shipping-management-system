<?php
// تفعيل عرض الأخطاء للمساعدة في تصحيح أي مشاكل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تضمين ملف الاتصال بقاعدة البيانات
include 'db_connect.php';

// التحقق من ID الوكيل من الرابط أو الجلسة
$agent_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['login_id']) ? intval($_SESSION['login_id']) : 0);

// إذا لم يتم العثور على ID صالح، يتم إيقاف الصفحة برسالة خطأ واضحة
if ($agent_id == 0) {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">رقم الوكيل غير صحيح.</div></div>';
    exit;
}

// جلب بيانات الوكيل للتأكد من وجوده
$agent_qry = $conn->query("SELECT * FROM agents WHERE id = $agent_id");
if ($agent_qry->num_rows > 0) {
    $agent_data = $agent_qry->fetch_assoc();
} else {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">الوكيل غير موجود.</div></div>';
    exit;
}

// تعريف مصفوفة حالات الشحنات
$status_arr = array(
    "تم قبول الشحنة من المندوب", "تم الاستلام", "تم الشحن", "جاري التوصيل",
    "وصلت إلى الوجهة", "في طريقها للتسليم", "جاهزة للاستلام",
    "تم التوصيل", "تم استلامها", "فشلت محاولة التوصيل"
);

// بناء استعلام SQL
$query_parts = [];
$params = [];
$types = '';

$query_parts[] = "p.agent_id = ?";
$params[] = $agent_id;
$types .= 'i';

$filter_status = isset($_GET['status']) ? intval($_GET['status']) : -1;
if ($filter_status > -1) {
    $query_parts[] = "p.status = ?";
    $params[] = $filter_status;
    $types .= 'i';
}

$filter_area = isset($_GET['area']) ? trim($_GET['area']) : '';
if (!empty($filter_area)) {
    $query_parts[] = "p.to_area LIKE ?";
    $params[] = "%$filter_area%";
    $types .= 's';
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
if (!empty($start_date) && !empty($end_date)) {
    $query_parts[] = "DATE(p.date_created) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= 'ss';
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search_term)) {
    $query_parts[] = "(p.reference_number LIKE ? OR p.recipient_name LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $types .= 'ss';
}

$query = "SELECT p.* FROM parcels p WHERE " . implode(' AND ', $query_parts) . " ORDER BY p.date_created DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $shipments = $stmt->get_result();
} else {
    $shipments = null;
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">خطأ في إعداد الاستعلام.</div></div>';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شحنات الوكيل</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap">
    <style>
        body { font-family: 'Tajawal', Arial, sans-serif; direction: rtl; background-color: #f4f7f6; }
        .card { border-radius: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .card-header { background-color: #17a2b8; color: white; border-radius: 1rem 1rem 0 0; padding: 1.5rem; text-align: center; }
        .table thead th { background-color: #17a2b8; color: #fff; border-color: #17a2b8; }
        .table tbody tr:hover { background-color: #e9ecef; }
        .form-control, .btn { border-radius: 0.5rem; }
        .filter-container { background-color: #e9ecef; border-radius: 1rem; padding: 20px; margin-bottom: 25px; }
        .btn-info { background-color: #17a2b8; border-color: #17a2b8; }
        .btn-info:hover { background-color: #138496; border-color: #138496; }
        .status-badge { font-size: 0.8rem; padding: 0.4em 0.8em; border-radius: 50rem; font-weight: bold; }

        /* ألوان مخصصة لكل حالة */
        .status-0 { background-color: #6c757d; color: #fff; } /* تم قبول الشحنة (رمادي) */
        .status-1 { background-color: #ffc107; color: #333; } /* تم الاستلام (أصفر) */
        .status-2 { background-color: #0d6efd; color: #fff; } /* تم الشحن (أزرق) */
        .status-3 { background-color: #28a745; color: #fff; } /* جاري التوصيل (أخضر) */
        .status-4 { background-color: #17a2b8; color: #fff; } /* وصلت إلى الوجهة (أزرق سماوي) */
        .status-5 { background-color: #fd7e14; color: #fff; } /* في طريقها للتسليم (برتقالي) */
        .status-6 { background-color: #6f42c1; color: #fff; } /* جاهزة للاستلام (بنفسجي) */
        .status-7 { background-color: #20c997; color: #fff; } /* تم التوصيل (أخضر فاتح) */
        .status-8 { background-color: #007bff; color: #fff; } /* تم استلامها (أزرق داكن) */
        .status-9 { background-color: #dc3545; color: #fff; } /* فشلت محاولة التوصيل (أحمر) */
        
        .action-buttons .btn { margin-left: 5px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-boxes me-2"></i> شحنات الوكيل: <?php echo htmlspecialchars($agent_data['company_name']); ?></h4>
        </div>
        <div class="card-body">
            <div class="filter-container">
                <form id="filterForm" class="row g-3" method="GET">
                    <input type="hidden" name="page" value="agent_shipments">
                    <input type="hidden" name="id" value="<?php echo $agent_id; ?>">
                    
                    <div class="col-md-4">
                        <label for="search_input" class="form-label">بحث سريع</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search_input" name="search" placeholder="رقم الشحنة أو اسم المستلم" value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="status_select" class="form-label">حالة الشحنة</label>
                        <select class="form-select" id="status_select" name="status">
                            <option value="-1" <?php echo $filter_status == -1 ? 'selected' : ''; ?>>جميع الحالات</option>
                            <?php foreach($status_arr as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filter_status == $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="area_input" class="form-label">المنطقة</label>
                        <input type="text" class="form-control" id="area_input" name="area" placeholder="اسم المنطقة" value="<?php echo htmlspecialchars($filter_area); ?>">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info w-100"><i class="fas fa-filter me-2"></i> تصفية</button>
                    </div>
                </form>
                <hr>
                <form id="dateFilterForm" class="row g-3" method="GET">
                    <input type="hidden" name="page" value="agent_shipments">
                    <input type="hidden" name="id" value="<?php echo $agent_id; ?>">
                    <input type="hidden" name="status" value="<?php echo $filter_status; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <input type="hidden" name="area" value="<?php echo htmlspecialchars($filter_area); ?>">

                    <div class="col-md-4">
                        <label for="start_date_input" class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" id="start_date_input" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="end_date_input" class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" id="end_date_input" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-info w-100"><i class="fas fa-calendar-alt me-2"></i> تصفية بالتاريخ</button>
                    </div>
                </form>
            </div>
            
            <?php if($shipments && $shipments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المرجع</th>
                            <th>المستلم</th>
                            <th>المنطقة</th>
                            <th>القيمة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $shipments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['reference_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['to_area'] ?? ''); ?></td>
                            <td><?php echo number_format($row['price'] ?? 0, 2); ?> جنيه</td>
                            <td>
                                <?php
                                $status = $row['status'] ?? 0;
                                echo '<span class="badge status-badge status-'.htmlspecialchars($status).'">'.htmlspecialchars($status_arr[$status] ?? 'غير معروف').'</span>';
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($row['date_created'] ?? '')); ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editShipmentModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-reference="<?php echo htmlspecialchars($row['reference_number'] ?? ''); ?>"
                                    data-recipient="<?php echo htmlspecialchars($row['recipient_name'] ?? ''); ?>"
                                    data-area="<?php echo htmlspecialchars($row['to_area'] ?? ''); ?>"
                                    data-price="<?php echo htmlspecialchars($row['price'] ?? ''); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="index.php?page=parcel_details&id=<?php echo $row['id'] ?? ''; ?>" class="btn btn-sm btn-info" title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">لا توجد شحنات مطابقة لخيارات البحث.</div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="index.php?page=agent_profile&id=<?php echo $agent_id; ?>" class="btn btn-outline-secondary mx-1"><i class="fas fa-arrow-right"></i> العودة لملف الوكيل</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editShipmentModal" tabindex="-1" aria-labelledby="editShipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editShipmentModalLabel">تعديل الشحنة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editShipmentForm">
          <input type="hidden" id="edit_shipment_id" name="id">
          <div class="mb-3">
            <label for="edit_reference" class="form-label">المرجع</label>
            <input type="text" class="form-control" id="edit_reference" name="reference_number" required>
          </div>
          <div class="mb-3">
            <label for="edit_recipient" class="form-label">اسم المستلم</label>
            <input type="text" class="form-control" id="edit_recipient" name="recipient_name" required>
          </div>
          <div class="mb-3">
            <label for="edit_area" class="form-label">المنطقة</label>
            <input type="text" class="form-control" id="edit_area" name="to_area" required>
          </div>
          <div class="mb-3">
            <label for="edit_price" class="form-label">القيمة</label>
            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="edit_status" class="form-label">الحالة</label>
            <select class="form-select" id="edit_status" name="status" required>
                <?php foreach($status_arr as $key => $value): ?>
                    <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($value); ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary w-100">حفظ التغييرات</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// عند الضغط على زر التعديل، قم بملء النموذج المنبثق بالبيانات
$(document).on('click', '.edit-btn', function() {
    $('#edit_shipment_id').val($(this).data('id'));
    $('#edit_reference').val($(this).data('reference'));
    $('#edit_recipient').val($(this).data('recipient'));
    $('#edit_area').val($(this).data('area'));
    $('#edit_price').val($(this).data('price'));
    $('#edit_status').val($(this).data('status'));
});

// عند إرسال نموذج التعديل داخل النافذة المنبثقة
$('#editShipmentForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();

    if (confirm("هل أنت متأكد من حفظ التغييرات على هذه الشحنة؟")) {
        $.ajax({
            url: 'update_parcel_data.php', // اسم الملف الجديد
            type: 'POST',
            data: formData,
            success: function(response) {
                alert(response);
                location.reload(); // إعادة تحميل الصفحة لرؤية التغييرات
            },
            error: function(xhr, status, error) {
                alert("حدث خطأ أثناء حفظ التغييرات: " + error);
            }
        });
    }
});

// كود تحديث الحالة القديم (لا يزال يعمل)
$(document).on('click', '.update-status', function(e) {
    e.preventDefault();
    var parcelId = $(this).data('id');
    var newStatus = $(this).data('status');
    if (confirm("هل أنت متأكد من تغيير حالة هذه الشحنة؟")) {
        $.ajax({
            url: 'update_parcel_status.php',
            type: 'POST',
            data: { id: parcelId, status: newStatus },
            success: function(response) {
                alert(response);
                location.reload();
            },
            error: function(xhr, status, error) {
                alert("حدث خطأ: " + error);
            }
        });
    }
});
</script>

</body>
</html>