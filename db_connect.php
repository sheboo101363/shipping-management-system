<?php
// بيانات الاتصال بقاعدة البيانات
$db_host = 'localhost';
$db_user = 'u239043057_admin1';
$db_pass = 'V~t:k|3t';
$db_name = 'u239043057_shipping';

// إنشاء اتصال جديد
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// التحقق من الاتصال
if ($conn->connect_error) {
    // إيقاف التنفيذ وإرجاع استجابة JSON صحيحة
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error
    ]);
    exit();
}

// *** هذا هو السطر المهم الذي يجب إضافته لإصلاح مشكلة التشفير ***
$conn->set_charset("utf8mb4");

?>