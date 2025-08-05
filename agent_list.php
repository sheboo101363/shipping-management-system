<?php
// تضمين ملف الاتصال بقاعدة البيانات
include 'db_connect.php';

// جلب بيانات الوكلاء من قاعدة البيانات
$agents = $conn->query("SELECT * FROM agents ORDER BY id DESC");

// دالة لتغيير حالة الوكيل
function getStatusBadge($status) {
    if ($status == 1) {
        return '<span class="badge bg-success">نشط</span>';
    } else {
        return '<span class="badge bg-secondary">موقوف</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الوكلاء</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap">
    <style>
        body { font-family: 'Tajawal', Arial, sans-serif; direction: rtl; background-color: #f4f7f6; }
        .card { border-radius: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .card-header { background-color: #17a2b8; color: white; border-radius: 1rem 1rem 0 0; padding: 1.5rem; text-align: center; }
        .table thead th { background-color: #17a2b8; color: #fff; border-color: #17a2b8; }
        .table tbody tr:hover { background-color: #e9ecef; }
        .action-buttons .btn { margin-left: 5px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0 text-center"><i class="fas fa-users me-2"></i> قائمة الوكلاء</h4>
        </div>
        <div class="card-body">
            <div class="text-start mb-3">
                <a href="index.php?page=new_agent" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة وكيل جديد</a>
            </div>
            <?php if ($agents->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered text-center">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الشركة</th>
                            <th>المسؤول</th>
                            <th>رقم الجوال</th>
                            <th>البريد الإلكتروني</th>
                            <th>النطاق الجغرافي</th>
                            <th>العمولة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $agents->fetch_assoc()): ?>
                        <tr id="agent-row-<?php echo $row['id']; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['geo_area']); ?></td>
                            <td><?php echo htmlspecialchars($row['commission_value']); ?> (<?php echo htmlspecialchars($row['commission_type']); ?>)</td>
                            <td><?php echo getStatusBadge($row['status']); ?></td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editAgentModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-companyname="<?php echo htmlspecialchars($row['company_name']); ?>"
                                    data-contactperson="<?php echo htmlspecialchars($row['contact_person']); ?>"
                                    data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-geoarea="<?php echo htmlspecialchars($row['geo_area']); ?>"
                                    data-address="<?php echo htmlspecialchars($row['address']); ?>"
                                    data-cooptype="<?php echo htmlspecialchars($row['coop_type']); ?>"
                                    data-commissiontype="<?php echo htmlspecialchars($row['commission_type']); ?>"
                                    data-commissionvalue="<?php echo htmlspecialchars($row['commission_value']); ?>"
                                    data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="index.php?page=agent_profile&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="عرض الملف الشخصي"><i class="fas fa-eye"></i></a>
                                <button type="button" class="btn btn-sm <?php echo $row['status'] == 1 ? 'btn-secondary' : 'btn-success'; ?> toggle-status-btn" data-id="<?php echo $row['id']; ?>" data-status="<?php echo $row['status']; ?>" title="تغيير الحالة">
                                    <i class="fas <?php echo $row['status'] == 1 ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-agent-btn" data-id="<?php echo $row['id']; ?>" title="حذف الوكيل">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">لا يوجد وكلاء مسجلون حاليًا.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editAgentModalLabel">تعديل بيانات الوكيل</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editAgentForm">
          <input type="hidden" id="edit_agent_id" name="id">
          <div class="mb-3">
            <label for="edit_company_name" class="form-label">اسم الشركة *</label>
            <input type="text" class="form-control" id="edit_company_name" name="company_name" required>
          </div>
          <div class="mb-3">
            <label for="edit_contact_person" class="form-label">اسم المسؤول</label>
            <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
          </div>
          <div class="mb-3">
            <label for="edit_geo_area" class="form-label">النطاق الجغرافي</label>
            <input type="text" class="form-control" id="edit_geo_area" name="geo_area">
          </div>
          <div class="mb-3">
            <label for="edit_address" class="form-label">العنوان</label>
            <input type="text" class="form-control" id="edit_address" name="address">
          </div>
          <div class="mb-3">
            <label for="edit_phone" class="form-label">رقم الجوال</label>
            <input type="text" class="form-control" id="edit_phone" name="phone">
          </div>
          <div class="mb-3">
            <label for="edit_email" class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" id="edit_email" name="email">
          </div>
          <div class="mb-3">
            <label for="edit_coop_type" class="form-label">نوع التعاون</label>
            <select name="coop_type" class="form-control" id="edit_coop_type">
              <option value="exchange">تبادل شحنات (شراكة متبادلة)</option>
              <option value="to_them">نرسل لهم الطلبيات فقط</option>
              <option value="from_them">يطلبون منا الشحن فقط</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_commission_type" class="form-label">نوع العمولة</label>
            <select name="commission_type" class="form-control" id="edit_commission_type">
              <option value="fixed">مبلغ ثابت لكل شحنة</option>
              <option value="percent">نسبة مئوية من قيمة الشحنة</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_commission_value" class="form-label">قيمة العمولة *</label>
            <input type="number" step="0.01" class="form-control" id="edit_commission_value" name="commission_value" required min="0">
          </div>
          <div class="mb-3">
            <label for="edit_status" class="form-label">الحالة</label>
            <select class="form-select" id="edit_status" name="status" required>
                <option value="1">نشط</option>
                <option value="0">موقوف</option>
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
    $('#edit_agent_id').val($(this).data('id'));
    $('#edit_company_name').val($(this).data('companyname'));
    $('#edit_contact_person').val($(this).data('contactperson'));
    $('#edit_phone').val($(this).data('phone'));
    $('#edit_email').val($(this).data('email'));
    $('#edit_geo_area').val($(this).data('geoarea'));
    $('#edit_address').val($(this).data('address'));
    $('#edit_coop_type').val($(this).data('cooptype'));
    $('#edit_commission_type').val($(this).data('commissiontype'));
    $('#edit_commission_value').val($(this).data('commissionvalue'));
    $('#edit_status').val($(this).data('status'));
});

// عند إرسال نموذج التعديل داخل النافذة المنبثقة
$('#editAgentForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();

    if (confirm("هل أنت متأكد من حفظ التغييرات على بيانات الوكيل؟")) {
        $.ajax({
            url: 'ajax.php?action=update_agent', // ملف معالجة التحديث
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

// كود jQuery لتغيير حالة الوكيل
$(document).on('click', '.toggle-status-btn', function() {
    var agentId = $(this).data('id');
    var currentStatus = $(this).data('status');
    var newStatus = (currentStatus == 1) ? 0 : 1;

    if (confirm("هل أنت متأكد من تغيير حالة هذا الوكيل؟")) {
        $.ajax({
            url: 'toggle_agent_status.php',
            type: 'POST',
            data: { id: agentId, status: newStatus },
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

// كود jQuery لحذف الوكيل
$(document).on('click', '.delete-agent-btn', function() {
    var agentId = $(this).data('id');

    if (confirm("تحذير! هل أنت متأكد من حذف هذا الوكيل نهائيًا؟ سيتم حذف جميع بياناته المرتبطة.")) {
        $.ajax({
            url: 'delete_agent.php',
            type: 'POST',
            data: { id: agentId },
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