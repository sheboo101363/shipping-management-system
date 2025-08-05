<?php
session_start();
include 'db_connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    // تعديل التحقق ليستخدم md5 كما هو مخزن في قاعدة البيانات
    $password_hash = md5($password);
    $res = $conn->query("SELECT * FROM couriers WHERE phone='$phone' AND password='$password_hash' LIMIT 1");
    if ($res->num_rows > 0) {
        $courier = $res->fetch_assoc();
        $_SESSION['courier_id'] = $courier['id'];
        header("Location: courier_dashboard.php");
        exit;
    } else {
        $error = 'رقم الجوال أو كلمة المرور غير صحيحة!';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تسجيل دخول المندوب</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 RTL + FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f3f6fa; min-height:100vh; }
        .login-box { max-width: 370px; margin: 48px auto; background: #fff; border-radius: 22px; box-shadow: 0 4px 18px #e0e6f3; padding: 38px 28px; }
        .logo-img { width: 85px; height: 85px; border-radius: 50%; object-fit:cover; margin-bottom: 18px; box-shadow:0 2px 12px #ddd;}
        .form-control { font-size: 1.11rem; border-radius: 12px; }
        .form-label { font-weight: bold; }
        .input-group-text { background: #e3f2fd; }
        .btn-login { font-size: 1.18rem; padding: 12px 0; border-radius: 16px; font-weight:bold;}
        .alert { font-size: 1.08rem; border-radius: 14px;}
        .show-pass { cursor:pointer; }
        .dark-mode {background: #181a1b;}
        .dark-mode .login-box {background:#23272f; color:#fff;}
        .dark-mode .form-control, .dark-mode .input-group-text {background:#181a1b;color:#fff;}
        .dark-mode .btn-login {background:#1565c0;}
        .dark-mode .alert-danger {background:#a93226;}
    </style>
</head>
<body>
<div class="container">
    <div class="login-box">
        <div class="text-center mb-2">
            <img src="avatar.png" alt="مندوب" class="logo-img">
            <h3 class="fw-bold mb-1 text-primary">مرحبا بعودتك!</h3>
            <div class="text-muted">تسجيل دخول المندوب</div>
        </div>
        <?php if($error): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label"><i class="fa fa-mobile-alt"></i> رقم الجوال</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                    <input type="text" name="phone" class="form-control" required placeholder="أدخل رقم جوالك" maxlength="11">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fa fa-lock"></i> كلمة المرور</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-key"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="كلمة المرور">
                    <span class="input-group-text show-pass" onclick="togglePass()"><i class="fa fa-eye"></i></span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login mt-2"><i class="fa fa-sign-in-alt"></i> دخول</button>
        </form>
        <div class="mt-4 text-center">
            <button class="btn btn-dark" onclick="document.body.classList.toggle('dark-mode')"><i class="fa fa-moon"></i> تبديل الوضع الليلي</button>
        </div>
    </div>
</div>
<script>
function togglePass() {
    var pass = document.getElementById('password');
    var icon = document.querySelector('.show-pass i');
    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pass.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>