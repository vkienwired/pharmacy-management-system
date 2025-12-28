<?php

session_start(); 

if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);   
    $_SESSION['initiated'] = true; 
}

if (!empty($_SESSION['user_logged_in'])) {
    header("Location: ../Users_Admin/index.php"); 
    exit();
}

require_once __DIR__ . "/../Include/db.php"; 

$error = "";              
$phone_value = "";        
$login_disabled = false;  

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;      
    $_SESSION['last_attempt_time'] = 0;   
}

$current_time   = time(); 
$max_attempts   = 5;      
$block_duration = 300;    

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_since_last = $current_time - (int)$_SESSION['last_attempt_time'];
    if ($time_since_last < $block_duration) {
        $remaining = $block_duration - $time_since_last;
        $minutes = (int)ceil($remaining / 60);
        $error = "Tài khoản bị khóa tạm thời. Thử lại sau {$minutes} phút.";
        $login_disabled = true;
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$login_disabled) {

    $phone = trim($_POST['phone'] ?? "");      
    $password = (string)($_POST['password'] ?? "");
    $phone_value = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');

    // 1. Kiểm tra rỗng
    if ($phone === "" || $password === "") {
        $error = "Vui lòng nhập đầy đủ thông tin!";
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = $current_time;
    } 
    // 2. THÊM REGEX Ở ĐÂY ----------------------------------------------
    // Logic: 
    // - Nếu bắt đầu bằng số 0 -> Validate theo kiểu SĐT (chỉ số, 10-11 ký tự)
    // - Ngược lại -> Validate theo kiểu Username (chữ, số, _, -, .)
    elseif (preg_match('/^[0-9]+$/', $phone)) {
        // Là số nhưng không đúng định dạng SĐT Việt Nam
        if (!preg_match('/^0\d{9,10}$/', $phone)) {
            $error = "Số điện thoại không hợp lệ (Phải bắt đầu bằng 0 và có 10-11 số).";
            $_SESSION['login_attempts']++;
        }
    } 
    elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $phone)) {
        // Username chứa ký tự lạ
        $error = "Tên đăng nhập không hợp lệ (Chỉ chứa chữ, số và ký tự _, ., -).";
        $_SESSION['login_attempts']++;
    }

    else {
        // Nếu qua được Regex thì mới query DB
        $delay_ms = min(2000, $_SESSION['login_attempts'] * 300);
        usleep($delay_ms * 1000);

        try {
            $stmt = $pdo->prepare("
                SELECT id_nguoi_dung, ho_ten, ten_dang_nhap, so_dien_thoai, mat_khau_hash, trang_thai, id_vai_tro
                FROM nguoi_dung
                WHERE (so_dien_thoai = :login OR ten_dang_nhap = :login)
                LIMIT 1
            ");
            $stmt->execute([':login' => $phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = $current_time;
                $error = "Tài khoản không tồn tại!";
            } 
            elseif (($user['trang_thai'] ?? '') !== 'HOAT_DONG') {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = $current_time;
                $error = "Tài khoản đang bị khóa.";
            }
            else {
                $hash = (string)($user['mat_khau_hash'] ?? '');
                $ok = false;
                if (preg_match('/^\$2y\$/', $hash) || preg_match('/^\$argon2/', $hash)) {
                    $ok = password_verify($password, $hash);
                } else {
                    $ok = hash_equals($hash, $password);
                }

                if ($ok) {
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['last_attempt_time'] = 0;
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['id_nguoi_dung']  = (int)$user['id_nguoi_dung'];
                    $_SESSION['username']       = $user['ten_dang_nhap'];
                    $_SESSION['user_name']      = $user['ho_ten'];
                    $_SESSION['user_role_id']   = (int)$user['id_vai_tro'];
                    $_SESSION['login_time']     = $current_time;

                    header("Location: ../Users_Admin/index.php");
                    exit();
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = $current_time;
                    $remain = max(0, $max_attempts - $_SESSION['login_attempts']);
                    $error = "Mật khẩu không đúng! Còn {$remain} lần thử.";
                }
            }
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - PharmaManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: linear-gradient(135deg,#00b09b 0%,#96c93d 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .login-card { background:white; border-radius:20px; box-shadow:0 20px 40px rgba(0,0,0,0.15); padding:40px; max-width:450px; width:100%; }
        .logo-icon { font-size:3rem; color:#00b09b; text-align:center; margin-bottom:10px; }
        .btn-login { background:#00b09b; border:none; padding:14px; font-weight:600; width:100%; border-radius:12px; }
        .btn-login:hover:not(:disabled) { background:#008a7a; }
        .alert-custom { background: rgba(220,53,69,0.08); border-left:4px solid #dc3545; padding:12px 14px; border-radius:10px; color: #dc3545; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo-icon"><i class="fas fa-pills"></i></div>
    <h3 class="fw-bold text-center mb-1">PharmaManager</h3>
    <p class="text-muted text-center mb-4">Đăng nhập hệ thống</p>

    <?php if (!empty($error)): ?>
        <div class="alert-custom mb-3">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <div class="mb-3">
            <label class="form-label fw-bold">Số điện thoại / Username</label>
            <input type="text" name="phone" class="form-control p-3" placeholder="VD: 0912345678" required value="<?= $phone_value ?>" <?= $login_disabled ? 'disabled' : '' ?>>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Mật khẩu</label>
            <input type="password" name="password" class="form-control p-3" placeholder="••••••" required <?= $login_disabled ? 'disabled' : '' ?>>
        </div>

        <button type="submit" class="btn btn-primary btn-login" <?= $login_disabled ? 'disabled' : '' ?>>
            <i class="fas fa-sign-in-alt me-2"></i> Đăng Nhập
        </button>
    </form>
</div>
</body>
</html>