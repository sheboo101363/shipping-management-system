<?php
session_start();
ini_set('display_errors', 1);

class Action {
    private $db;

    public function __construct() {
        ob_start();
        include 'db_connect.php';
        $this->db = $conn;
    }

    function __destruct() {
        $this->db->close();
        ob_end_flush();
    }

    // الدالة المصححة لحساب تكلفة الشحن بناءً على المحافظة فقط
    function get_shipping_cost($gov_id) {
        $cost = 0;
        if ($gov_id > 0) {
            $qry = $this->db->query("SELECT price FROM governorates WHERE id = $gov_id LIMIT 1");
            if ($qry && $row = $qry->fetch_assoc()) {
                $cost = floatval($row['price']);
            }
        }
        return $cost;
    }

    function login(){
        extract($_POST);
        $qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users WHERE email = '".$email."' and password = '".md5($password)."'");
        if($qry->num_rows > 0){
            foreach ($qry->fetch_array() as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            return 1;
        }else{
            return 2;
        }
    }

    function logout(){
        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        header("location:login.php");
    }

    function login2(){
        extract($_POST);
        // تعديل: لو لديك تسجيل دخول للمندوبين أو موظفين آخرين عدله هنا أو أضف تحقق md5 إذا كلمة المرور مشفرة
        $qry = $this->db->query("SELECT *,concat(name) as name FROM couriers WHERE phone = '".$phone."' and password = '".md5($password)."'");
        if($qry->num_rows > 0){
            foreach ($qry->fetch_array() as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['courier_'.$key] = $value;
            }
            return 1;
        }else{
            return 3;
        }
    }

    function save_user(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        if(!empty($password)){
            $data .= ", password='".md5($password)."' ";
        }
        $check = $this->db->query("SELECT * FROM users WHERE email ='".addslashes($email)."' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            return 1;
        }
    }

    function signup(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
                if($k =='password'){
                    if(empty($v))
                        continue;
                    $v = md5($v);
                }
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        $check = $this->db->query("SELECT * FROM users WHERE email ='".addslashes($email)."' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/uploads/'. $fname);
            $data .= ", avatar = '$fname' ";
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            if(empty($id))
                $id = $this->db->insert_id;
            foreach ($_POST as $key => $value) {
                if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            $_SESSION['login_id'] = $id;
            return 1;
        }
    }

    function update_user(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','cpass','table')) && !is_numeric($k)){
                if($k =='password')
                    $v = md5($v);
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
            $data .= ", avatar = '$fname' ";
        }
        $check = $this->db->query("SELECT * FROM users WHERE email ='".addslashes($email)."' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
            exit;
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO users set $data");
        }else{
            $save = $this->db->query("UPDATE users set $data where id = $id");
        }
        if($save){
            foreach ($_POST as $key => $value) {
                if($key != 'password' && !is_numeric($key))
                    $_SESSION['login_'.$key] = $value;
            }
            if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != '')
                $_SESSION['login_avatar'] = $fname;
            return 1;
        }
    }

    function delete_user(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM users where id = ".$id);
        if($delete)
            return 1;
    }

    function save_system_settings(){
        extract($_POST);
        $data = '';
        foreach($_POST as $k => $v){
            if(!is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
            $move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
            $data .= ", cover_img = '$fname' ";
        }
        $chk = $this->db->query("SELECT * FROM system_settings");
        if($chk->num_rows > 0){
            $save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
        }else{
            $save = $this->db->query("INSERT INTO system_settings set $data");
        }
        if($save){
            foreach($_POST as $k => $v){
                if(!is_numeric($k)){
                    $_SESSION['system'][$k] = $v;
                }
            }
            if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
                $_SESSION['system']['cover_img'] = $fname;
            }
            return 1;
        }
    }

    function save_image(){
        extract($_FILES['file']);
        if(!empty($tmp_name)){
            $fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
            $move = move_uploaded_file($tmp_name,'../assets/uploads/'. $fname);
            $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
            $hostName = $_SERVER['HTTP_HOST'];
            $path =explode('/',$_SERVER['PHP_SELF']);
            $currentPath = '/'.$path[1];
            if($move){
                return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
            }
        }
    }

    function save_branch(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id')) && !is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        if(empty($id)){
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $i = 0;
            while($i == 0){
                $bcode = substr(str_shuffle($chars), 0, 15);
                $chk = $this->db->query("SELECT * FROM branches where branch_code = '$bcode'")->num_rows;
                if($chk <= 0){
                    $i = 1;
                }
            }
            $data .= ", branch_code='".addslashes($bcode)."' ";
            $save = $this->db->query("INSERT INTO branches set $data");
        }else{
            $save = $this->db->query("UPDATE branches set $data where id = $id");
        }
        if($save){
            return 1;
        }
    }

    function delete_branch(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM branches where id = $id");
        if($delete){
            return 1;
        }
    }

    function save_courier(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','password','cpass')) && !is_numeric($k)){
                if(empty($data)){
                    $data .= " $k='".addslashes($v)."' ";
                }else{
                    $data .= ", $k='".addslashes($v)."' ";
                }
            }
        }
        if(!empty($password)){
            $data .= ", password='".md5($password)."' ";
        }
        $check = $this->db->query("SELECT * FROM couriers WHERE email ='".addslashes($email)."' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
        if($check > 0){
            return 2;
        }
        if(empty($id)){
            $save = $this->db->query("INSERT INTO couriers set $data");
        }else{
            $save = $this->db->query("UPDATE couriers set $data where id = $id");
        }
        if($save){
            return "تم حفظ بيانات المندوب بنجاح.";
        }
    }

    function delete_courier(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM couriers where id = ".$id);
        if($delete){
            return "تم حذف المندوب بنجاح.";
        }
        return "حدث خطأ أثناء حذف المندوب.";
    }

    function update_agent(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, ['id'])) {
                if(!empty($data)) $data .= ", ";
                $data .= "`{$k}` = '".addslashes($v)."'";
            }
        }
        $sql = "UPDATE `agents` SET {$data} WHERE id = '{$id}'";
        $update = $this->db->query($sql);
        if($update){
            return "تم تحديث بيانات الوكيل بنجاح.";
        } else {
            return "حدث خطأ في تحديث البيانات: " . $this->db->error;
        }
    }

    // الدالة المصححة لإضافة شحنة جديدة و معالجة منطق ربط المندوب والوكيل
    function save_parcel(){
        extract($_POST);

        // تحقق من أن price مصفوفة وليس نص
        if (isset($price) && !is_array($price)) {
            $price = [$price];
        }
        if (isset($weight) && !is_array($weight)) {
            $weight = [$weight];
        }
        if (isset($height) && !is_array($height)) {
            $height = [$height];
        }
        if (isset($width) && !is_array($width)) {
            $width = [$width];
        }
        if (isset($length) && !is_array($length)) {
            $length = [$length];
        }

        // تأمين وتحضير المتغيرات
        $sender_name = isset($sender_name) ? $this->db->real_escape_string($sender_name) : '';
        $sender_address = isset($sender_address) ? $this->db->real_escape_string($sender_address) : '';
        $sender_phone = isset($sender_phone) ? $this->db->real_escape_string($sender_phone) : '';
        $sender_email = isset($sender_email) ? $this->db->real_escape_string($sender_email) : '';
        $sender_governorate_id = isset($sender_governorate_id) ? intval($sender_governorate_id) : 0;
        $sender_area_id = isset($sender_area_id) ? intval($sender_area_id) : 0;
        $sender_status = isset($sender_status) ? intval($sender_status) : 1;

        $recipient_name = isset($recipient_name) ? $this->db->real_escape_string($recipient_name) : '';
        $recipient_address = isset($recipient_address) ? $this->db->real_escape_string($recipient_address) : '';
        $recipient_phone = isset($recipient_phone) ? $this->db->real_escape_string($recipient_phone) : '';
        $recipient_email = isset($recipient_email) ? $this->db->real_escape_string($recipient_email) : '';
        $recipient_governorate_id = isset($recipient_governorate_id) ? intval($recipient_governorate_id) : 0;
        $recipient_area_id = isset($recipient_area_id) ? intval($recipient_area_id) : 0;
        $recipient_status = isset($recipient_status) ? intval($recipient_status) : 1;

        $courier_id = isset($courier_id) ? intval($courier_id) : 0;
        $from_branch_id = isset($from_branch_id) ? intval($from_branch_id) : 0;
        $to_branch_id = isset($to_branch_id) ? intval($to_branch_id) : 0;
        $type = isset($type) ? intval($type) : 2;
        $agent_id = isset($agent_id) && !empty($agent_id) ? intval($agent_id) : 0;
        $agent_id_to_use = $agent_id > 0 ? $agent_id : (isset($_SESSION['login_id']) ? $_SESSION['login_id'] : 0);

        // ------------ منطق ربط المندوب/الوكيل الصحيح ------------

        // إذا الشحنة تابعة لوكيل، لا تربط أي مندوب نهائيًا
        if ($agent_id > 0) {
            $courier_id = 0;
        } else {
            // توزيع مندوب تلقائي (إذا لم يتم اختياره يدويًا)
            if (empty($courier_id) && !empty($recipient_area_id)) {
                // جلب أول مندوب مرتبط بهذه المنطقة من جدول courier_areas
                $courier_res = $this->db->query("SELECT courier_id FROM courier_areas WHERE area_id = $recipient_area_id LIMIT 1");
                if ($courier_res && $courier = $courier_res->fetch_assoc()) {
                    $courier_id = intval($courier['courier_id']);
                }
            }
        }
        // ----------------------------------------------------------

        // البحث عن العميل وإضافته إذا لم يكن موجوداً
        $customer_id = 0;
        if($sender_name && $sender_phone){
            $customer_check = $this->db->query("SELECT id FROM customers WHERE phone='{$sender_phone}' OR (email <> '' AND email='{$sender_email}')");
            if($customer_check->num_rows == 0){
                $insert_customer = $this->db->query("INSERT INTO customers
                (name, phone, email, address, governorate_id, area_id, status)
                VALUES (
                    '{$sender_name}',
                    '{$sender_phone}',
                    '{$sender_email}',
                    '{$sender_address}',
                    {$sender_governorate_id},
                    {$sender_area_id},
                    {$sender_status}
                )");
                if($insert_customer){
                    $customer_id = $this->db->insert_id;
                } else {
                    return "خطأ في إضافة العميل: " . $this->db->error;
                }
            }else{
                $row = $customer_check->fetch_assoc();
                $customer_id = $row['id'];
            }
        }

        // حفظ الشحنة
        $ids = [];
        foreach($price as $k => $v){
            $data = "sender_name='{$sender_name}'";
            $data .= ",sender_address='{$sender_address}'";
            $data .= ",sender_contact='{$sender_phone}'";
            $data .= ",sender_phone='{$sender_phone}'";
            $data .= ",sender_email='{$sender_email}'";
            $data .= ",sender_governorate_id={$sender_governorate_id}";
            $data .= ",sender_area_id={$sender_area_id}";
            $data .= ",sender_status={$sender_status}";
            $data .= ",recipient_name='{$recipient_name}'";
            $data .= ",recipient_address='{$recipient_address}'";
            $data .= ",recipient_contact='{$recipient_phone}'";
            $data .= ",recipient_phone='{$recipient_phone}'";
            $data .= ",recipient_email='{$recipient_email}'";
            $data .= ",recipient_governorate_id={$recipient_governorate_id}";
            $data .= ",recipient_area_id={$recipient_area_id}";
            $data .= ",recipient_status={$recipient_status}";
            $data .= ",courier_id={$courier_id}";
            $data .= ",from_branch_id={$from_branch_id}";
            $data .= ",to_branch_id={$to_branch_id}";
            $data .= ",type={$type}";

            $data .= ",weight='".(isset($weight[$k]) ? $this->db->real_escape_string($weight[$k]) : '0')."'";
            $data .= ",height='".(isset($height[$k]) ? $this->db->real_escape_string($height[$k]) : '0')."'";
            $data .= ",width='".(isset($width[$k]) ? $this->db->real_escape_string($width[$k]) : '0')."'";
            $data .= ",length='".(isset($length[$k]) ? $this->db->real_escape_string($length[$k]) : '0')."'";

            $item_price = isset($price[$k]) ? str_replace(',', '', $price[$k]) : 0;
            $data .= ",price='".floatval($item_price)."'";
            $data .= ",status='0'";
            $data .= ", agent_id='{$agent_id_to_use}'";

            if(empty($id)){
                $i = 0;
                while($i == 0){
                    $ref = sprintf("%'012d",mt_rand(0, 999999999999));
                    $chk = $this->db->query("SELECT * FROM parcels where reference_number = '$ref'")->num_rows;
                    if($chk <= 0){
                        $i = 1;
                    }
                }
                $data .= ",reference_number='{$ref}' ";
                if($this->db->query("INSERT INTO parcels set $data")){
                    $ids[]= $this->db->insert_id;
                }
            } else {
                if($this->db->query("UPDATE parcels set $data where id = $id")){
                    $ids[] = $id;
                }
            }
        }

        if(isset($ids) && count($ids) > 0){
            return "تم حفظ الشحنة بنجاح.";
        } else {
            return "حدث خطأ غير متوقع أثناء حفظ الشحنة.";
        }
    }

    function delete_parcel(){
        extract($_POST);
        $delete = $this->db->query("DELETE FROM parcels where id = $id");
        if($delete){
            return 1;
        }
    }

    function update_parcel(){
        extract($_POST);
        $id = intval($id);
        $status = intval($status);
        $update = $this->db->query("UPDATE parcels set status= $status where id = $id");
        $save = $this->db->query("INSERT INTO parcel_tracks set status= $status , parcel_id = $id");
        if($update && $save)
            return 1;
        else
            return "حدث خطأ: " . $this->db->error;
    }

    function get_parcel_heistory(){
        extract($_POST);
        $data = array();
        $parcel = $this->db->query("SELECT * FROM parcels where reference_number = '$ref_no'");
        if($parcel->num_rows <=0){
            return 2;
        }else{
            $parcel = $parcel->fetch_array();
            $data[] = array('status'=>'Item accepted by Courier','date_created'=>date("M d, Y h:i A",strtotime($parcel['date_created'])));
            $history = $this->db->query("SELECT * FROM parcel_tracks where parcel_id = {$parcel['id']}");
            $status_arr = array("Item Accepted by Courier","Collected","Shipped","In-Transit","Arrived At Destination","Out for Delivery","Ready to Pickup","Delivered","Picked-up","Unsuccessfull Delivery Attempt");
            while($row = $history->fetch_assoc()){
                $row['date_created'] = date("M d, Y h:i A",strtotime($row['date_created']));
                $row['status'] = $status_arr[$row['status']];
                $data[] = $row;
            }
            return json_encode($data);
        }
    }

    // دالة get_report المصححة
    function get_report(){
        $date_from = isset($_POST['date_from']) ? $this->db->real_escape_string($_POST['date_from']) : null;
        $date_to = isset($_POST['date_to']) ? $this->db->real_escape_string($_POST['date_to']) : null;
        $status = isset($_POST['status']) ? $this->db->real_escape_string($_POST['status']) : 'all';

        $data = array();
        $sql = "SELECT p.*, c.name as courier_name, g.name as governorate_name 
                FROM parcels p 
                LEFT JOIN couriers c ON p.courier_id = c.id 
                LEFT JOIN governorates g ON p.recipient_governorate_id = g.id 
                WHERE 1=1";
        
        if (!is_null($date_from) && !is_null($date_to)) {
            $sql .= " AND DATE(p.date_created) BETWEEN '{$date_from}' AND '{$date_to}'";
        }
        
        if ($status != 'all') {
            $sql .= " AND p.status = '{$status}'";
        }
        
        $sql .= " ORDER BY unix_timestamp(p.date_created) ASC";

        $get = $this->db->query($sql);
        if ($get) {
            $status_arr = array("Item Accepted by Courier","Collected","Shipped","In-Transit","Arrived At Destination","Out for Delivery","Ready to Pickup","Delivered","Picked-up","Unsuccessfull Delivery Attempt");
            while($row = $get->fetch_assoc()){
                $row['sender_name'] = ucwords($row['sender_name']);
                $row['recipient_name'] = ucwords($row['recipient_name']);
                $row['date_created'] = date("M d, Y",strtotime($row['date_created']));
                $row['status'] = $status_arr[$row['status']];
                $row['price'] = number_format($row['price'],2);
                $data[] = $row;
            }
        }
        return json_encode($data);
    }

    function save_customer(){
        extract($_POST);
        $name = isset($name) ? $this->db->real_escape_string($name) : '';
        $phone = isset($phone) ? $this->db->real_escape_string($phone) : '';
        $email = isset($email) ? $this->db->real_escape_string($email) : '';
        $address = isset($address) ? $this->db->real_escape_string($address) : '';
        $governorate_id = isset($governorate_id) ? intval($governorate_id) : 0;
        $area_id = isset($area_id) ? intval($area_id) : 0;
        $status = isset($status) ? intval($status) : 1;
        $check = $this->db->query("SELECT id FROM customers WHERE phone='{$phone}' OR (email <> '' AND email='{$email}')")->num_rows;
        if($check > 0){
            return 2;
        }
        $save = $this->db->query("INSERT INTO customers (name, phone, email, address, governorate_id, area_id, status) VALUES ('{$name}', '{$phone}', '{$email}', '{$address}', {$governorate_id}, {$area_id}, {$status})");
        if($save){
            return 1;
        }else{
            return "خطأ في حفظ العميل: ".$this->db->error;
        }
    }
    
    public function update_customer() {
        extract($_POST);
        $id = intval($id);
        $name = $this->db->real_escape_string($name);
        $phone = $this->db->real_escape_string($phone);
        $email = $this->db->real_escape_string($email);
        $address = $this->db->real_escape_string($address);
        $governorate_id = intval($governorate_id);
        $area_id = intval($area_id);
        $status = intval($status);
        $qry = $this->db->query("UPDATE customers SET name='{$name}', phone='{$phone}', email='{$email}', address='{$address}', governorate_id={$governorate_id}, area_id={$area_id}, status={$status} WHERE id=$id");
        if ($qry) {
            return 1;
        } else {
            return "خطأ في تحديث بيانات العميل: " . $this->db->error;
        }
    }
    
    public function delete_customer() {
        extract($_POST);
        $id = intval($id);
        $customer_qry = $this->db->query("SELECT phone FROM customers WHERE id = $id");
        if ($customer_qry->num_rows > 0) {
            $customer = $customer_qry->fetch_assoc();
            $customer_phone = $this->db->real_escape_string($customer['phone']);
            $this->db->query("DELETE FROM parcels WHERE sender_phone = '{$customer_phone}'");
            $this->db->query("DELETE FROM customers WHERE id = $id");
            return 1;
        } else {
            return 2;
        }
    }

    public function add_governorate(){
        extract($_POST);
        $name = $this->db->real_escape_string($gov_name);
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $check = $this->db->query("SELECT id FROM governorates WHERE name = '{$name}'")->num_rows;
        if($check > 0){
            return 2;
        }
        $save = $this->db->query("INSERT INTO governorates (name, price) VALUES ('{$name}', {$price})");
        if($save){
            return 1;
        }
        return 0;
    }

    public function update_governorate(){
        extract($_POST);
        $id = intval($id);
        $name = $this->db->real_escape_string($gov_name);
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $check = $this->db->query("SELECT id FROM governorates WHERE name = '{$name}' AND id != {$id}")->num_rows;
        if($check > 0){
            return 2;
        }
        $update = $this->db->query("UPDATE governorates SET name = '{$name}', price = {$price} WHERE id = {$id}");
        if($update){
            return 1;
        }
        return 0;
    }

    public function delete_governorate(){
        extract($_POST);
        $id = intval($id);
        $check_areas = $this->db->query("SELECT id FROM areas WHERE governorate_id = $id")->num_rows;
        if($check_areas > 0){
            return 2;
        }
        $delete = $this->db->query("DELETE FROM governorates WHERE id = $id");
        if($delete){
            return 1;
        }
        return 0;
    }

    public function add_area(){
        extract($_POST);
        $gov_id = intval($governorate_id);
        $name = $this->db->real_escape_string($area_name);
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $check = $this->db->query("SELECT id FROM areas WHERE name = '{$name}' AND governorate_id = {$gov_id}")->num_rows;
        if($check > 0){
            return 2;
        }
        $save = $this->db->query("INSERT INTO areas (governorate_id, name, price) VALUES ({$gov_id}, '{$name}', {$price})");
        if($save){
            return 1;
        }
        return 0;
    }

    public function update_area(){
        extract($_POST);
        $id = intval($id);
        $gov_id = intval($governorate_id);
        $name = $this->db->real_escape_string($area_name);
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $check = $this->db->query("SELECT id FROM areas WHERE name = '{$name}' AND governorate_id = {$gov_id} AND id != {$id}")->num_rows;
        if($check > 0){
            return 2;
        }
        $update = $this->db->query("UPDATE areas SET name = '{$name}', governorate_id = {$gov_id}, price = {$price} WHERE id = {$id}");
        if($update){
            return 1;
        }
        return 0;
    }

    public function delete_area(){
        extract($_POST);
        $id = intval($id);
        $delete = $this->db->query("DELETE FROM areas WHERE id = $id");
        if($delete){
            return 1;
        }
        return 0;
    }
}
?>