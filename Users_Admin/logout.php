<?php


session_start();

// Kiểm tra nếu đã xác nhận đăng xuất
$confirmed = $_GET['confirmed'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $confirmed === 'true') {
    // Lưu thông tin trước khi đăng xuất
    $user_name = $_SESSION['user_name'] ?? 'Người dùng';
    $login_time = $_SESSION['login_time'] ?? time();
    $session_duration = time() - $login_time;
    
    // Ghi log đăng xuất
    error_log("[LOGOUT] $user_name đã đăng xuất. Thời gian phiên: " . gmdate("H:i:s", $session_duration) . 
              " - IP: " . $_SERVER['REMOTE_ADDR']);
    
    // Xóa tất cả biến session
    $_SESSION = [];
    
    // Xóa session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Hủy session
    session_destroy();
    
    // Chống cache
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    

    header("Location: login.php?logout=success&duration=" . $session_duration);
    exit();
} else {
    // Hiển thị trang xác nhận đăng xuất
    ?>
    <!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đăng xuất - PharmaManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #00b09b;
            --primary-dark: #008a7a;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 4rem;
            color: var(--warning-color);
            margin-bottom: 20px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-confirm {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover, .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-cancel:active, .btn-confirm:active {
            transform: translateY(0);
        }
        
        .session-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .alert-warning-custom {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid var(--warning-color);
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .duration-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h3 class="fw-bold text-dark mb-3">Xác nhận đăng xuất</h3>
        
        <p class="text-muted mb-4">
            Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?
        </p>
        
        <div class="session-info">
            <h6><i class="fas fa-user-circle me-2 text-primary"></i>Thông tin phiên làm việc:</h6>
            <div class="row mt-2">
                <div class="col-6">
                    <small class="text-muted">Người dùng:</small><br>
                    <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Không xác định') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted">Vai trò:</small><br>
                    <strong>ID #<?= htmlspecialchars($_SESSION['user_role_id'] ?? 'N/A') ?></strong>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6">
                    <small class="text-muted">Thời gian đăng nhập:</small><br>
                    <strong><?= isset($_SESSION['login_time']) ? date('H:i:s', $_SESSION['login_time']) : 'N/A' ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted">Ngày:</small><br>
                    <strong><?= isset($_SESSION['login_time']) ? date('d/m/Y', $_SESSION['login_time']) : 'N/A' ?></strong>
                </div>
            </div>
        </div>
        
        <div class="alert-warning-custom">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Cảnh báo:</strong> Tất cả dữ liệu chưa lưu sẽ bị mất khi đăng xuất.
        </div>
        
        <div class="d-flex gap-3 justify-content-center mt-4">
            <button type="button" class="btn btn-cancel" id="backButton">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </button>
            <button type="button" class="btn btn-confirm" id="logoutButton">
                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
            </button>
        </div>
        
        <div class="mt-4 text-muted small">
            <i class="fas fa-lightbulb me-1"></i>
            Lưu ý: Đảm bảo đã lưu tất cả thay đổi trước khi đăng xuất
        </div>
        
        <form id="logoutForm" method="POST" style="display: none;"></form>
    </div>
    
    <script>
        // Xử lý nút Quay lại
        document.getElementById('backButton').addEventListener('click', function() {

            // Nếu có referrer hợp lệ thì dùng, không thì về index
            if (document.referrer && document.referrer !== '' && document.referrer !== window.location.href && !document.referrer.includes('login.php')) {
                window.location.href = document.referrer;
            } else {
                window.location.href = '../Users_Admin/index.php'; 
            }
        });
        
        // Xử lý nút Đăng xuất
        document.getElementById('logoutButton').addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?\n\nTất cả dữ liệu chưa lưu sẽ bị mất.')) {
                // Disable cả 2 nút
                document.getElementById('backButton').disabled = true;
                document.getElementById('logoutButton').disabled = true;
                
                // Hiển thị loading
                const logoutBtn = document.getElementById('logoutButton');
                const originalText = logoutBtn.innerHTML;
                logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang đăng xuất...';
                
                // Gửi form logout
                document.getElementById('logoutForm').submit();
            }
        });
        
        // Phím tắt
        document.addEventListener('keydown', function(e) {
            // Esc để quay lại
            if (e.key === 'Escape') {
                document.getElementById('backButton').click();
            }
            
            // Enter để đăng xuất
            if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('logoutButton').click();
            }
        });
        
        // Auto-focus vào nút Quay lại
        document.getElementById('backButton').focus();
    </script>
</body>
</html>
    <?php
    exit();
}
?>