<?php
// تفعيل عرض الأخطاء للمساعدة في تصحيح أي مشاكل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تضمين ملف الاتصال بقاعدة البيانات
include 'db_connect.php';

// المنطق الأساسي: التحقق من وجود ID الوكيل في الرابط، وإلا يتم استخدام ID الجلسة
$agent_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['login_id']) ? $_SESSION['login_id'] : 0);

// إذا لم يتم العثور على ID صالح، يتم إيقاف الصفحة برسالة خطأ واضحة
if ($agent_id == 0) {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">الوكيل غير موجود.</div></div>';
    exit;
}

// جلب بيانات الوكيل من قاعدة البيانات
$agent = $conn->query("SELECT * FROM agents WHERE id = $agent_id")->fetch_assoc();
if(!$agent){
  echo '<div class="container mt-5"><div class="alert alert-danger text-center">الوكيل غير موجود</div></div>';
  exit;
}

// احصائيات الشحنات (تأكد من اسم العمود في جدول parcels: استخدم price أو القيمة الصحيحة بدل amount)
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(price) as total FROM parcels WHERE agent_id = $agent_id");
$data = $res->fetch_assoc();
$shipments_count = $data['cnt'] ?? 0;
$total_amount = $data['total'] ?? 0;
?>

<div class="container py-5" style="background:#f5f7fa;min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="card shadow-lg border-0 rounded-4 mb-4">
        <div class="card-header bg-gradient-primary text-white rounded-top-4">
          <h3 class="mb-0"><i class="fas fa-user-tie"></i> تفاصيل وكيل الشحن: <?php echo htmlspecialchars($agent['company_name']) ?></h3>
        </div>
        <div class="card-body bg-white">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-gradient-info text-white rounded-top-3">
                  <h6 class="mb-0"><i class="fas fa-building"></i> بيانات الشركة</h6>
                </div>
                <div class="card-body pb-2">
                  <ul class="list-group list-group-flush mb-2">
                    <li class="list-group-item"><b>اسم الشركة:</b> <?php echo htmlspecialchars($agent['company_name'] ?? '') ?></li>
                    <li class="list-group-item"><b>اسم المسؤول:</b> <?php echo htmlspecialchars($agent['contact_person'] ?? '') ?></li>
                    <li class="list-group-item"><b>العنوان:</b> <?php echo htmlspecialchars($agent['address'] ?? '') ?></li>
                    <li class="list-group-item"><b>النطاق الجغرافي:</b> <?php echo htmlspecialchars($agent['geo_area'] ?? '') ?></li>
                  </ul>
                  <h6 class="text-muted mt-3 mb-2"><i class="fas fa-phone"></i> التواصل</h6>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item"><b>رقم الجوال:</b> <?php echo htmlspecialchars($agent['phone'] ?? '') ?></li>
                    <li class="list-group-item"><b>البريد الإلكتروني:</b> <?php echo htmlspecialchars($agent['email'] ?? '') ?></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-gradient-warning text-white rounded-top-3">
                  <h6 class="mb-0"><i class="fas fa-handshake"></i> التعاون والعمولة</h6>
                </div>
                <div class="card-body pb-2">
                  <ul class="list-group list-group-flush mb-2">
                    <li class="list-group-item">
                      <b>نوع التعاون:</b>
                      <?php
                        $types = [
                          'to_them' => 'نرسل لهم الطلبيات',
                          'from_them' => 'يطلبون منا الشحن',
                          'exchange' => 'تبادل شحنات'
                        ];
                        echo isset($types[$agent['coop_type']]) ? $types[$agent['coop_type']] : '-';
                      ?>
                    </li>
                    <li class="list-group-item">
                      <b>العمولة:</b>
                      <?php
                        if($agent['commission_type'] == 'percent')
                          echo htmlspecialchars($agent['commission_value']) . ' % لكل شحنة';
                        elseif($agent['commission_type'] == 'fixed')
                          echo htmlspecialchars($agent['commission_value']) . ' جنيه لكل شحنة';
                        else
                          echo '-';
                      ?>
                    </li>
                    <li class="list-group-item">
                      <b>العلاقة الوظيفية:</b>
                      <?php
                        if($agent['coop_type'] == 'exchange')
                          echo 'شراكة متبادلة (كل طرف يشحن للآخر)';
                        else
                          echo 'وكالة (طرف يشحن للآخر)';
                      ?>
                    </li>
                    <li class="list-group-item">
                      <b>الحالة:</b>
                      <?php echo $agent['status'] == 1 ? '<span class="badge bg-success px-3 py-2">نشط</span>' : '<span class="badge bg-secondary px-3 py-2">موقوف</span>'; ?>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="row g-4 mt-2">
            <div class="col-md-12">
              <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-gradient-success text-white rounded-top-3">
                  <h6 class="mb-0"><i class="fas fa-chart-bar"></i> إحصائيات الوكيل</h6>
                </div>
                <div class="card-body pb-2">
                  <div class="row text-center">
                    <div class="col-md-6 mb-2">
                      <div class="stat-box bg-light rounded-3 p-3 mb-2">
                        <span class="stat-title text-muted">عدد الشحنات المرتبطة</span>
                        <h4 class="stat-num text-primary mb-0"><?php echo $shipments_count ?></h4>
                      </div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <div class="stat-box bg-light rounded-3 p-3 mb-2">
                        <span class="stat-title text-muted">إجمالي المبالغ للشحنات (جنيه)</span>
                        <h4 class="stat-num text-warning mb-0"><?php echo number_format($total_amount,2) ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-4">
            <div class="col-lg-12 text-center">
              <a href="index.php?page=agent_shipments&id=<?php echo $agent['id'] ?>" class="btn btn-outline-secondary mx-2 rounded-pill"><i class="fas fa-box"></i> عرض الشحنات المتبادلة</a>
              <a href="index.php?page=agent_finance&id=<?php echo $agent['id'] ?>" class="btn btn-outline-warning mx-2 rounded-pill"><i class="fas fa-file-invoice-dollar"></i> تقارير الحسابات والعمولات</a>
              <a href="index.php?page=agent_list" class="btn btn-outline-dark mx-2 rounded-pill"><i class="fas fa-list"></i> العودة لقائمة الوكلاء</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
body { background: #f5f7fa !important; }
.card { border-radius: 1.5rem !important; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
.card-header { border-radius: 1.5rem 1.5rem 0 0 !important; font-size: 1.1rem; }
.list-group-item { background: #f7f7f7; font-size: 1.08rem; border: none; }
.btn { border-radius: 2rem !important; font-weight: 500; font-size: 1.07rem; }
.stat-box { box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
.stat-title { font-size: 0.97rem; }
.stat-num { font-weight: bold; font-size: 1.6rem; }
.bg-gradient-primary { background: linear-gradient(90deg,#007bff 0,#5bc0de 100%) !important; }
.bg-gradient-success { background: linear-gradient(90deg,#28a745 0,#88e07c 100%) !important; }
.bg-gradient-warning { background: linear-gradient(90deg,#ffc107 0,#fff176 100%) !important; color:#6c4d00 !important; }
.bg-gradient-info { background: linear-gradient(90deg,#17a2b8 0,#77eaff 100%) !important; }
</style>