<?php include'db_connect.php'; ?>
<div class="col-lg-12">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <div class="card-tools">
        <a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="./index.php?page=new_customer"><i class="fa fa-plus"></i> إضافة عميل جديد</a>
      </div>
    </div>
    <div class="card-body">
      <table class="table table-hover table-bordered" id="customer-list">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>اسم العميل</th>
            <th>رقم الجوال</th>
            <th>البريد الإلكتروني</th>
            <th>العنوان</th>
            <th>الحالة</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $qry = $conn->query("SELECT * FROM customers ORDER BY name ASC");
          while($row = $qry->fetch_assoc()):
          ?>
          <tr>
            <td class="text-center"><?php echo $i++ ?></td>
            <td><?php echo htmlspecialchars($row['name']) ?></td>
            <td><?php echo htmlspecialchars($row['phone']) ?></td>
            <td><?php echo htmlspecialchars($row['email']) ?></td>
            <td><?php echo htmlspecialchars($row['address']) ?></td>
            <td>
              <?php echo $row['status'] == 1 ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-secondary">موقوف</span>'; ?>
            </td>
            <td class="text-center">
              <div class="btn-group">
                <!-- زر عرض الشحنات والمندوب المسؤول -->
                <button type="button" 
                        class="btn btn-info btn-flat btn-sm view-shipments" 
                        data-id="<?php echo $row['id'] ?>" 
                        title="الشحنات والمندوب"><i class="fas fa-truck-loading"></i></button>
                <!-- تعديل -->
                <a href="index.php?page=edit_customer&id=<?php echo $row['id'] ?>" class="btn btn-primary btn-flat btn-sm"><i class="fas fa-edit"></i></a>
                <!-- حذف -->
                <button class="btn btn-danger btn-delete-customer" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></button>
                <!-- زر تفاصيل إضافي -->
                <button type="button" class="btn btn-secondary btn-flat btn-sm view-customer-details" data-id="<?php echo $row['id'] ?>"><i class="fas fa-user"></i></button>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- مودال الشحنات والمندوب -->
<div class="modal fade" id="shipmentsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">شحنات العميل والمندوب المسؤول</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="shipments-content">
        <div class="text-center"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- مودال تفاصيل العميل -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل العميل</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="customer-details-content">
        <div class="text-center"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- نافذة منبثقة مخصصة لحذف العميل -->
<div id="deleteCustomerPopup" style="display:none; position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.2);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;padding:28px 22px;border-radius:10px;max-width:360px;text-align:center;box-shadow:0 4px 24px #aaa;">
    <h4 style="color:#b71c1c;">تحذير حذف العميل</h4>
    <p style="color:#333;font-weight:bold;">هذا القرار لا يمكن الرجوع عنه!</p>
    <div style="margin:14px 0;">
      <label>
        <input type="radio" name="deleteType" value="customer_only" checked>
        حذف العميل فقط (الشحنات ستبقى)
      </label><br>
      <label>
        <input type="radio" name="deleteType" value="customer_and_shipments">
        حذف العميل وجميع الشحنات المرتبطة
      </label>
    </div>
    <button type="button" class="btn btn-danger" id="confirmDeleteCustomer">حذف نهائي</button>
    <button type="button" class="btn btn-secondary" id="cancelDeleteCustomer">إلغاء</button>
  </div>
</div>

<script>
$(function(){
  $('#customer-list').DataTable();

  // عرض الشحنات والمندوب
  $('.view-shipments').on('click', function(){
    var customer_id = $(this).data('id');
    $('#shipments-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div>');
    $('#shipmentsModal').modal('show');
    $.post('ajax.php?action=get_customer_shipments', {customer_id: customer_id}, function(resp){
      $('#shipments-content').html(resp);
    });
  });

  // تفاصيل العميل
  $('.view-customer-details').on('click', function(){
    var customer_id = $(this).data('id');
    $('#customer-details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> جاري التحميل...</div>');
    $('#customerDetailsModal').modal('show');
    $.post('ajax.php?action=get_customer_details', {customer_id: customer_id}, function(resp){
      $('#customer-details-content').html(resp);
    });
  });

  // زر الحذف (معدل ليعمل بشكل صحيح)
  var currentCustomerId = null;
  $('body').on('click', '.btn-delete-customer', function(){
    currentCustomerId = $(this).data('id');
    $('#deleteCustomerPopup').css('display','flex');
  });
  $('body').on('click', '#cancelDeleteCustomer', function(){
    $('#deleteCustomerPopup').css('display','none');
    currentCustomerId = null;
  });
  $('body').on('click', '#confirmDeleteCustomer', function(){
    var deleteType = $('input[name="deleteType"]:checked').val();
    $.post('ajax.php?action=delete_customer', {id: currentCustomerId, delete_type: deleteType}, function(resp){
      if(resp == 1){
        alert('تم الحذف بنجاح');
        location.reload();
      }else{
        alert(resp); // سيظهر رسالة الخطأ الحقيقية إذا فشل الحذف
      }
      $('#deleteCustomerPopup').css('display','none');
      currentCustomerId = null;
    });
  });

  $('#editCustomerForm').submit(function(e){
    e.preventDefault();
    $.post('ajax.php?action=update_customer', $(this).serialize(), function(resp){
      if(resp == 1){
        alert('تم تعديل بيانات العميل بنجاح');
        location.reload();
      }else{
        alert('فشل التعديل');
      }
    });
  });
});
</script>