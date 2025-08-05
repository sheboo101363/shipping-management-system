<?php if(!isset($conn)){ include 'db_connect.php'; } ?>
<div class="col-lg-6">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <form id="manage-customer">
        <input type="hidden" name="id" value="">
        <div id="msg" class=""></div>
        <div class="form-group">
          <label>اسم العميل</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>رقم الجوال</label>
          <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
          <label>العنوان</label>
          <input type="text" name="address" class="form-control">
        </div>
        <div class="form-group">
          <label>المحافظة</label>
          <select name="governorate_id" id="customer-gov" class="form-control" required>
            <option value="">اختر المحافظة</option>
            <?php
            $govs = $conn->query("SELECT * FROM governorates ORDER BY name ASC");
            while($g = $govs->fetch_assoc()):
            ?>
            <option value="<?php echo $g['id'] ?>"><?php echo $g['name'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>المنطقة</label>
          <select name="area_id" id="customer-area" class="form-control" required>
            <option value="">اختر المنطقة</option>
          </select>
        </div>
        <div class="form-group">
          <label>الحالة</label>
          <select name="status" class="form-control">
            <option value="1">نشط</option>
            <option value="0">موقوف</option>
          </select>
        </div>
        <button class="btn btn-success">حفظ</button>
        <a class="btn btn-secondary" href="./index.php?page=customer_list">إلغاء</a>
      </form>
    </div>
  </div>
</div>
<script>
$('#customer-gov').change(function(){
  var gov_id = $(this).val();
  $('#customer-area').html('<option value="">اختر المنطقة</option>');
  if(gov_id){
    $.post('ajax.php?action=get_areas_by_gov', {gov_id: gov_id}, function(resp){
      $('#customer-area').html(resp);
    });
  }
});
$('#manage-customer').submit(function(e){
  e.preventDefault();
  $.ajax({
    url:'ajax.php?action=save_customer',
    method:'POST',
    data: $(this).serialize(),
    success:function(resp){
      if(resp == 1){
        alert("تم الحفظ بنجاح");
        setTimeout(function(){
          location.href = 'index.php?page=customer_list'
        },1000);
      } else {
        $('#msg').html('<div class="alert alert-danger">'+resp+'</div>');
      }
    }
  });
});
</script>