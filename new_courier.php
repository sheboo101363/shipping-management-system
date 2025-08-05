<?php if(!isset($conn)){ include 'db_connect.php'; } ?>
<div class="col-lg-12">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <form action="" id="manage-courier">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div id="msg" class=""></div>
        <div class="form-group">
          <label>اسم المندوب</label>
          <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? $name : '' ?>">
        </div>
        <div class="form-group">
          <label>رقم الجوال</label>
          <input type="text" name="phone" class="form-control" required value="<?php echo isset($phone) ? $phone : '' ?>">
        </div>
        <div class="form-group">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" value="<?php echo isset($email) ? $email : '' ?>">
        </div>
        <div class="form-group">
          <label>كلمة المرور <?php echo isset($id) ? '(اتركها فارغة إذا لا تريد تغييرها)' : '' ?></label>
          <input type="password" name="password" class="form-control" <?php echo !isset($id) ? 'required' : '' ?>>
        </div>
        <div class="form-group">
          <label>الفرع</label>
          <select name="branch_id" class="form-control" required>
            <option value="">اختر الفرع</option>
            <?php
            $branches = $conn->query("SELECT * FROM branches");
            while($row = $branches->fetch_assoc()):
            ?>
            <option value="<?php echo $row['id'] ?>" <?php echo isset($branch_id) && $branch_id == $row['id'] ? "selected":'' ?>><?php echo $row['branch_code'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>المحافظة</label>
          <select name="governorate_id" id="courier-gov" class="form-control" required>
            <option value="">اختر المحافظة</option>
            <?php
            $govs = $conn->query("SELECT * FROM governorates ORDER BY name ASC");
            while($g = $govs->fetch_assoc()):
            ?>
            <option value="<?php echo $g['id'] ?>" data-price="<?php echo $g['price'] ?>" <?php echo (isset($governorate_id) && $governorate_id == $g['id']) ? 'selected' : '' ?>>
              <?php echo $g['name'] ?>
            </option>
            <?php endwhile; ?>
          </select>
          <div id="gov-shipping-price" class="mt-2" style="display:none; font-weight:bold; color:#00695c;"></div>
        </div>
        <div class="form-group">
          <label>المنطقة</label>
          <select name="area_id" id="courier-area" class="form-control" required>
            <option value="">اختر المنطقة</option>
            <?php
            // المناطق فقط في حالة التعديل
            if(isset($governorate_id)){
              $areas = $conn->query("SELECT * FROM areas WHERE governorate_id = ".intval($governorate_id)." ORDER BY name ASC");
              while($a = $areas->fetch_assoc()):
              ?>
                <option value="<?php echo $a['id'] ?>" data-price="<?php echo $a['price'] ?>" <?php echo (isset($area_id) && $area_id == $a['id']) ? 'selected' : '' ?>>
                  <?php echo $a['name'] ?>
                </option>
              <?php
              endwhile;
            }
            ?>
          </select>
          <div id="area-shipping-price" class="mt-2" style="display:none; font-weight:bold; color:#ff9800;"></div>
        </div>
        <div class="form-group">
          <label>الحالة</label>
          <select name="status" class="form-control">
            <option value="1" <?php echo (isset($status) && $status == 1) ? "selected" : "" ?>>نشط</option>
            <option value="0" <?php echo (isset($status) && $status == 0) ? "selected" : "" ?>>موقوف</option>
          </select>
        </div>
      </form>
    </div>
    <div class="card-footer border-top border-info">
      <div class="d-flex w-100 justify-content-center align-items-center">
        <button class="btn btn-flat  bg-gradient-primary mx-2" form="manage-courier">حفظ</button>
        <a class="btn btn-flat bg-gradient-secondary mx-2" href="./index.php?page=courier_list">إلغاء</a>
      </div>
    </div>
  </div>
</div>
<script>
function formatPrice(price) {
  return parseFloat(price) ? (parseFloat(price).toFixed(2) + " ج") : "غير محدد";
}
$(function(){
  // عند تغيير المحافظة
  $('#courier-gov').change(function(){
    var gov_id = $(this).val();
    var gov_name = $('#courier-gov option:selected').text().trim();
    var gov_price = $('#courier-gov option:selected').data('price');
    // إظهار سعر شحن المحافظة
    if(gov_id && gov_price !== undefined){
      $('#gov-shipping-price').show().text("سعر شحن المحافظة: " + formatPrice(gov_price));
    } else {
      $('#gov-shipping-price').hide();
    }
    // جلب المناطق المرتبطة بالمحافظة
    $('#courier-area').html('<option value="">اختر المنطقة</option>');
    $('#area-shipping-price').hide();
    if(gov_id){
      $.post('ajax.php?action=get_areas_by_gov', {gov_id: gov_id}, function(resp){
        $('#courier-area').html(resp);
      });
    }
  });

  // عند تغيير المنطقة
  $('#courier-area').on('change', function(){
    var gov_name = $('#courier-gov option:selected').text().trim();
    var area_price = $('#courier-area option:selected').data('price');
    if(gov_name == 'دمياط' && area_price !== undefined && area_price != "" && area_price != "0"){
      $('#area-shipping-price').show().text("سعر شحن المنطقة: " + formatPrice(area_price));
    } else {
      $('#area-shipping-price').hide();
    }
  });

  // تفعيل تلقائي عند تحميل الصفحة في حالة التعديل
  <?php if(isset($governorate_id)): ?>
    $('#courier-gov').trigger('change');
    <?php if(isset($area_id)): ?>
      setTimeout(function(){
        $('#courier-area').val('<?php echo $area_id ?>').trigger('change');
      }, 500);
    <?php endif; ?>
  <?php endif; ?>

  $('#manage-courier').submit(function(e){
    e.preventDefault()
    start_load()
    $.ajax({
      url:'ajax.php?action=save_courier',
      data: new FormData($(this)[0]),
      cache: false,
      contentType: false,
      processData: false,
      method: 'POST',
      type: 'POST',
      success:function(resp){
        if(resp == 1){
          alert_toast('تم الحفظ بنجاح',"success");
          setTimeout(function(){
            location.href = 'index.php?page=courier_list'
          },2000)
        } else {
          alert_toast("حدث خطأ: " + resp, "error");
          end_load && end_load();
        }
      }
    })
  })
});
</script>