<?php
include'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $geo_area = $_POST['geo_area'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $coop_type = $_POST['coop_type'] ?? 'exchange';
    $commission_type = $_POST['commission_type'] ?? 'fixed';
    $commission_value = floatval($_POST['commission_value'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO agents (company_name, contact_person, geo_area, address, phone, email, coop_type, commission_type, commission_value, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssdi", $company_name, $contact_person, $geo_area, $address, $phone, $email, $coop_type, $commission_type, $commission_value, $status);

    if ($stmt->execute()) {
        echo "<script>alert('تم إضافة الوكيل بنجاح'); location.href='index.php?page=agent_list';</script>";
        exit;
    } else {
        echo "<div class='alert alert-danger'>حدث خطأ أثناء الإضافة!</div>";
    }
}
?>

<div class="col-lg-7 mx-auto">
  <div class="card card-outline card-primary shadow-lg">
    <div class="card-header bg-gradient-primary text-white">
      <h4 class="card-title mb-0"><i class="fas fa-user-tie"></i> إضافة وكيل/شركة شحن جديدة</h4>
    </div>
    <div class="card-body bg-light">
      <form method="post" autocomplete="off">
        <div class="form-group">
          <label><i class="fas fa-building"></i> اسم الشركة *</label>
          <input type="text" name="company_name" required class="form-control rounded-pill">
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> اسم المسؤول</label>
          <input type="text" name="contact_person" class="form-control rounded-pill">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marked-alt"></i> النطاق الجغرافي</label>
          <input type="text" name="geo_area" class="form-control rounded-pill" placeholder="مثال: القاهرة الكبرى، الدلتا ...">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-pin"></i> العنوان</label>
          <input type="text" name="address" class="form-control rounded-pill">
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label><i class="fas fa-phone"></i> رقم الجوال</label>
            <input type="text" name="phone" class="form-control rounded-pill" maxlength="20">
          </div>
          <div class="form-group col-md-6">
            <label><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control rounded-pill" maxlength="80">
          </div>
        </div>
        <div class="form-group">
          <label><i class="fas fa-handshake"></i> نوع التعاون</label>
          <select name="coop_type" class="form-control rounded-pill">
            <option value="exchange">تبادل شحنات (شراكة متبادلة)</option>
            <option value="to_them">نرسل لهم الطلبيات فقط</option>
            <option value="from_them">يطلبون منا الشحن فقط</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label><i class="fas fa-percentage"></i> نوع العمولة</label>
            <select name="commission_type" class="form-control rounded-pill">
              <option value="fixed">مبلغ ثابت لكل شحنة</option>
              <option value="percent">نسبة مئوية من قيمة الشحنة</option>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label><i class="fas fa-money-bill-wave"></i> قيمة العمولة *</label>
            <input type="number" step="0.01" name="commission_value" required class="form-control rounded-pill" placeholder="مثال: 20 أو 5.5">
          </div>
        </div>
        <div class="form-group form-check">
          <input type="checkbox" name="status" class="form-check-input" checked id="statusCheck">
          <label class="form-check-label" for="statusCheck">نشط</label>
        </div>
        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fas fa-save"></i> حفظ الوكيل</button>
        <a href="index.php?page=agent_list" class="btn btn-secondary rounded-pill px-4">إلغاء</a>
      </form>
    </div>
  </div>
</div>