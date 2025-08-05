<?php
if(!isset($conn)){ include 'db_connect.php'; }
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$c = $conn->query("SELECT c.*, g.name as gov_name, a.name as area_name 
    FROM customers c
    LEFT JOIN governorates g ON c.governorate_id = g.id
    LEFT JOIN areas a ON c.area_id = a.id
    WHERE c.id = $id")->fetch_assoc();
?>
<div class="col-lg-10 mx-auto">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title">تفاصيل العميل</h3>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr>
          <th>اسم العميل</th>
          <td><?php echo htmlspecialchars($c['name']) ?></td>
        </tr>
        <tr>
          <th>رقم الجوال</th>
          <td><?php echo htmlspecialchars($c['phone']) ?></td>
        </tr>
        <tr>
          <th>البريد الإلكتروني</th>
          <td><?php echo htmlspecialchars($c['email']) ?></td>
        </tr>
        <tr>
          <th>العنوان</th>
          <td><?php echo htmlspecialchars($c['address']) ?></td>
        </tr>
        <tr>
          <th>المحافظة</th>
          <td><?php echo htmlspecialchars($c['gov_name']) ?></td>
        </tr>
        <tr>
          <th>المنطقة</th>
          <td><?php echo htmlspecialchars($c['area_name']) ?></td>
        </tr>
        <tr>
          <th>الحالة</th>
          <td><?php echo $c['status'] ? "نشط" : "موقوف" ?></td>
        </tr>
        <tr>
          <th>تاريخ الإضافة</th>
          <td><?php echo htmlspecialchars($c['date_created']) ?></td>
        </tr>
      </table>
      <hr>
      <h5>سجل الشحنات</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>رقم الشحنة</th>
            <th>المندوب المسؤول</th>
            <th>الحالة</th>
            <th>تاريخ</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $shipments = $conn->query("SELECT s.*, cr.name as courier_name 
            FROM shipments s
            LEFT JOIN couriers cr ON s.courier_id = cr.id
            WHERE s.customer_id = $id ORDER BY s.id DESC");
          $i = 1;
          while($sh = $shipments->fetch_assoc()):
          ?>
          <tr>
            <td><?php echo $i++ ?></td>
            <td><?php echo $sh['id'] ?></td>
            <td><?php echo htmlspecialchars($sh['courier_name']) ?></td>
            <td><?php echo htmlspecialchars($sh['status']) ?></td>
            <td><?php echo htmlspecialchars($sh['created_at']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <hr>
      <h5>سجل العمليات</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>العملية</th>
            <th>المستخدم</th>
            <th>تاريخ ووقت</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $logs = $conn->query("SELECT * FROM customer_logs WHERE customer_id = $id ORDER BY created_at DESC");
          while($log = $logs->fetch_assoc()):
        ?>
          <tr>
            <td><?php echo htmlspecialchars($log['action']) ?></td>
            <td><?php echo htmlspecialchars($log['username']) ?></td>
            <td><?php echo htmlspecialchars($log['created_at']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>