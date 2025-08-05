<?php
ob_start();
date_default_timezone_set("Africa/Cairo");

$action = isset($_GET['action']) ? $_GET['action'] : '';
include 'admin_class.php';
$crud = new Action();

switch ($action) {
    case 'login':
        include 'db_connect.php';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $password_hash = md5($password);
        $qry = $conn->query("SELECT * FROM users WHERE email = '$email' AND password = '$password_hash' LIMIT 1");
        if($qry && $qry->num_rows > 0){
            $user = $qry->fetch_assoc();
            $_SESSION['login_id'] = $user['id'];
            $_SESSION['login_type'] = $user['type'];
            $_SESSION['login_name'] = $user['firstname'] . ' ' . $user['lastname'];
            echo 1;
        } else {
            echo 0;
        }
        break;

    case 'login2':
        echo $crud->login2();
        break;
    case 'logout':
        echo $crud->logout();
        break;
    case 'logout2':
        echo $crud->logout2();
        break;
    case 'signup':
        echo $crud->signup();
        break;
    case 'save_user':
        echo $crud->save_user();
        break;
    case 'update_user':
        echo $crud->update_user();
        break;
    case 'delete_user':
        echo $crud->delete_user();
        break;
    case 'save_branch':
        echo $crud->save_branch();
        break;
    case 'delete_branch':
        echo $crud->delete_branch();
        break;

    // ----------- أهم نقطة: هنا يتم حفظ الشحنة -----------
    case 'save_parcel':
        // لا داعي لأي تعديل هنا، لأن منطق ربط المندوب والوكيل أصبح مضبوط داخل دالة save_parcel في admin_class.php
        echo $crud->save_parcel();
        break;
    // -----------------------------------------------------

    case 'delete_parcel':
        echo $crud->delete_parcel();
        break;
    case 'update_parcel':
        echo $crud->update_parcel();
        break;
    case 'get_parcel_heistory':
        echo $crud->get_parcel_heistory();
        break;
    case 'get_report':
        echo $crud->get_report();
        break;
    case 'get_client_report':
        echo $crud->get_client_report();
        break;
    case 'get_agent_report':
        echo $crud->get_agent_report();
        break;
    case 'save_courier':
        echo $crud->save_courier();
        break;
    case 'delete_courier':
        echo $crud->delete_courier();
        break;
    case 'add_governorate':
        echo $crud->add_governorate();
        break;
    case 'update_governorate':
        echo $crud->update_governorate();
        break;
    case 'delete_governorate':
        echo $crud->delete_governorate();
        break;
    case 'add_area':
        echo $crud->add_area();
        break;
    case 'update_area':
        echo $crud->update_area();
        break;
    case 'delete_area':
        echo $crud->delete_area();
        break;
    case 'save_customer':
        echo $crud->save_customer();
        break;
    case 'update_customer':
        echo $crud->update_customer();
        break;
    case 'delete_customer':
        echo $crud->delete_customer();
        break;
    case 'update_agent':
        echo $crud->update_agent();
        break;
    case 'get_areas_by_gov':
        include 'db_connect.php';
        $gov_id = isset($_POST['gov_id']) ? intval($_POST['gov_id']) : 0;
        $areas = $conn->query("SELECT * FROM areas WHERE governorate_id = $gov_id ORDER BY name ASC");
        $out = '<option value="">اختر المنطقة</option>';
        while ($a = $areas->fetch_assoc()) {
            $out .= '<option value="' . $a['id'] . '">' . $a['name'] . '</option>';
        }
        echo $out;
        break;
    case 'get_customer_details':
        include 'db_connect.php';
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $c = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();
        if ($c) {
            echo '<table class="table table-bordered">';
            echo '<tr><th>اسم العميل</th><td>' . htmlspecialchars($c['name']) . '</td></tr>';
            echo '<tr><th>رقم الجوال</th><td>' . htmlspecialchars($c['phone']) . '</td></tr>';
            echo '<tr><th>البريد الإلكتروني</th><td>' . htmlspecialchars($c['email']) . '</td></tr>';
            echo '<tr><th>العنوان</th><td>' . htmlspecialchars($c['address']) . '</td></tr>';
            echo '<tr><th>الحالة</th><td>' . ($c['status'] ? "نشط" : "موقوف") . '</td></tr>';
            echo '<tr><th>تاريخ الإضافة</th><td>' . htmlspecialchars($c['date_created']) . '</td></tr>';
            echo '</table>';
        } else {
            echo '<div class="alert alert-danger">لم يتم العثور على العميل.</div>';
        }
        break;
    case 'get_customer_shipments':
        include 'db_connect.php';
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if (!$customer_id) {
            echo '<div class="alert alert-danger">معرف العميل غير صالح.</div>';
            break;
        }
        $customer = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();
        if (!$customer) {
            echo '<div class="alert alert-danger">لم يتم العثور على العميل.</div>';
            break;
        }
        $customer_phone = $conn->real_escape_string($customer['phone']);
        $status_arr = array(
            "تم قبول الطرد من المندوب",
            "تم استلام الطرد",
            "تم الشحن",
            "قيد النقل",
            "وصل للوجهة",
            "خرج للتسليم",
            "جاهز للاستلام",
            "تم التسليم",
            "تم الاستلام",
            "محاولة تسليم غير ناجحة"
        );
        $shipments_qry = $conn->query("SELECT p.*, c.name AS courier_name FROM parcels p LEFT JOIN couriers c ON p.courier_id = c.id WHERE p.sender_phone = '$customer_phone' ORDER BY p.id DESC");
        $num_shipments = $shipments_qry->num_rows;
        $status_count = array_fill(0, count($status_arr), 0);
        $total_balance = 0;
        $shipments_list = [];
        while ($row = $shipments_qry->fetch_assoc()) {
            $shipments_list[] = $row;
            $status_count[$row['status']]++;
            $total_balance += floatval($row['price']);
        }
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = 10;
        $total_pages = ceil(count($shipments_list) / $per_page);
        $start_idx = ($page - 1) * $per_page;
        $end_idx = min($start_idx + $per_page, count($shipments_list));
        $shipments_on_page = array_slice($shipments_list, $start_idx, $per_page);
        $couriers_res = $conn->query("SELECT id, name FROM couriers ORDER BY name ASC");
        $couriers = [];
        while ($cr = $couriers_res->fetch_assoc()) {
            $couriers[$cr['id']] = $cr['name'];
        }
        // طباعة HTML
        echo '<style>
        .customer-card {background: linear-gradient(135deg,#e0eafc 0%,#cfdef3 100%);border-radius: 15px;box-shadow: 0 8px 24px rgba(0,0,0,0.12);padding: 24px;margin-bottom: 18px;text-align: right;}
        .customer-card h5 {font-weight: bold;color:#1a237e;}
        .status-table th, .status-table td {text-align:center;}
        .status-table th {background:#1976d2;color:#fff;}
        .status-table tr td:first-child {background:#f6f7fb;}
        .status-table tr td:last-child {font-weight:bold;color:#388e3c;}
        .balance-bar {background: linear-gradient(90deg,#43e97b 0%,#38f9d7 100%);color:#222;font-size:1.2em;font-weight: bold;padding:10px 18px;border-radius:9px;margin-bottom:8px;box-shadow:0 2px 8px #38f9d766;text-align:center;}
        .ship-table {background:#fff;}
        .ship-table th {background: #00bcd4;color: #fff;text-align: center;}
        .ship-table td {text-align:center;}
        .pagination {display: flex;justify-content: center;margin-top: 12px;gap: 2px;}
        .pagination .page-item {display: inline-block;padding: 6px 13px;background: #e3e3e3;border-radius: 5px;margin: 0 1px;color: #1976d2;cursor: pointer;transition:background .2s;font-weight: bold;}
        .pagination .active {background: #1976d2;color: #fff;}
        .pagination .page-item:hover {background: #90caf9;color: #fff;}
        .change-status-btn {background: #388e3c;color: #fff;border: none;padding: 2px 8px;border-radius: 5px;font-size: 0.95em;margin-left:4px;transition:background .2s;cursor:pointer;}
        .change-status-btn:hover {background: #1b5e20;}
        </style>';
        echo "<div class='customer-card'>";
        echo "<h5>تفاصيل العميل: " . htmlspecialchars($customer['name']) . "</h5>";
        echo "<p><b>رقم الجوال:</b> " . htmlspecialchars($customer['phone']) . "</p>";
        echo "<p><b>البريد الإلكتروني:</b> " . htmlspecialchars($customer['email']) . "</p>";
        echo "<p><b>العنوان:</b> " . htmlspecialchars($customer['address']) . "</p>";
        echo "<p><b>عدد الشحنات:</b> " . $num_shipments . "</p>";
        echo "</div>";
        echo "<table class='table table-sm table-bordered status-table' style='max-width:400px;margin:auto;box-shadow:0 2px 11px #e0eafc;'>";
        echo "<thead><tr><th>الحالة</th><th>عدد الشحنات</th></tr></thead><tbody>";
        foreach ($status_arr as $idx => $status_name) {
            echo "<tr><td>" . htmlspecialchars($status_name) . "</td><td>" . $status_count[$idx] . "</td></tr>";
        }
        echo "</tbody></table>";
        if ($num_shipments) {
            echo "<h6 style='color:#1976d2;font-weight:bold;margin-top:24px'>قائمة الشحنات:</h6>";
            echo "<div style='overflow-x:auto;'><table class='table table-bordered ship-table'>";
            echo "<tr>
                <th>رقم الشحنة</th>
                <th>اسم المستلم</th>
                <th>رقم المستلم</th>
                <th>الحالة</th>
                <th>المندوب</th>
                <th>السعر</th>
                <th>تاريخ الإنشاء</th>
                <th>إجراء</th>
            </tr>";
            foreach ($shipments_on_page as $row) {
                echo "<tr>
                    <td>" . htmlspecialchars($row['reference_number']) . "</td>
                    <td>" . htmlspecialchars($row['recipient_name']) . "</td>
                    <td>" . htmlspecialchars($row['recipient_phone']) . "</td>
                    <td>
                        <form class='update-status-form' data-shipment='{$row['id']}'>
                            <select name='new_status' class='form-control form-control-sm' style='width:auto;display:inline-block'>";
                            foreach ($status_arr as $idx => $status_name) {
                                $selected = ($row['status'] == $idx) ? "selected" : "";
                                echo "<option value='$idx' $selected>" . htmlspecialchars($status_name) . "</option>";
                            }
                        echo "</select>
                        <button type='submit' class='change-status-btn'>حفظ</button>
                        </form>
                    </td>
                    <td>" . ($row['courier_name'] ? htmlspecialchars($row['courier_name']) : "<span style='color:#999'>غير محدد</span>") . "</td>
                    <td>" . number_format($row['price'], 2) . " جنيه</td>
                    <td>" . htmlspecialchars($row['date_created']) . "</td>
                    <td></td>
                </tr>";
            }
            echo "</table></div>";
            if ($total_pages > 1) {
                echo "<div class='pagination'>";
                for ($i = 1; $i <= $total_pages; $i++) {
                    $active = ($i == $page) ? "active" : "";
                    echo "<span class='page-item $active' data-page='$i'>$i</span>";
                }
                echo "</div>";
                echo "<script>
                $('#shipmentsModal .pagination .page-item').click(function(){
                    var page = $(this).data('page');
                    $.post('ajax.php?action=get_customer_shipments', {customer_id: {$customer_id}, page: page}, function(resp){
                        $('#shipments-content').html(resp);
                    });
                });
                </script>";
            }
            echo "<div class='balance-bar' style='margin-top:18px'>إجمالي الرصيد لجميع الشحنات: " . number_format($total_balance, 2) . " جنيه</div>";
            echo "<script>
            $('#shipmentsModal .update-status-form').submit(function(e){
                e.preventDefault();
                var form = $(this);
                var shipment_id = form.data('shipment');
                var new_status = form.find('[name=\"new_status\"]').val();
                form.find('.change-status-btn').prop('disabled', true).text('...');
                $.post('ajax.php?action=update_parcel_status', {id: shipment_id, status: new_status}, function(resp){
                    if(resp.trim() == '1'){
                        form.find('.change-status-btn').text('تم!');
                        setTimeout(function(){
                            $.post('ajax.php?action=get_customer_shipments', {customer_id: {$customer_id}, page: {$page}}, function(resp){
                                $('#shipments-content').html(resp);
                            });
                        }, 600);
                    }else{
                        form.find('.change-status-btn').prop('disabled', false).text('حفظ');
                        alert('حدث خطأ أثناء تحديث الحالة!');
                    }
                });
            });
            </script>";
        } else {
            echo "<div class='alert alert-info' style='margin-top:20px'>لا يوجد شحنات لهذا العميل.</div>";
        }
        break;
    case 'update_parcel_status':
        include 'db_connect.php';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        if ($id && $status !== null) {
            $update = $conn->query("UPDATE parcels SET status = $status WHERE id = $id");
            echo $update ? 1 : 0;
        } else {
            echo 0;
        }
        break;
    case 'search_customers':
        include 'db_connect.php';
        $query = isset($_POST['query']) ? $conn->real_escape_string($_POST['query']) : '';
        $q = $conn->query("SELECT * FROM customers WHERE name LIKE '%$query%' ORDER BY name ASC LIMIT 10");
        $result = [];
        while ($row = $q->fetch_assoc()) {
            $result[] = $row;
        }
        echo json_encode($result);
        break;
    case 'get_shipping_cost':
        include 'db_connect.php';
        $gov_id = isset($_POST['gov_id']) ? intval($_POST['gov_id']) : 0;
        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        $cost = 0;
        if ($area_id > 0) {
            $qry = $conn->query("SELECT price FROM areas WHERE id = $area_id LIMIT 1");
            if ($row = $qry->fetch_assoc()) {
                $cost = floatval($row['price']);
            }
        } elseif ($gov_id > 0) {
            $qry = $conn->query("SELECT price FROM governorates WHERE id = $gov_id LIMIT 1");
            if ($row = $qry->fetch_assoc()) {
                $cost = floatval($row['price']);
            }
        }
        echo $cost;
        break;
    default:
        break;
}

ob_end_flush();
?>