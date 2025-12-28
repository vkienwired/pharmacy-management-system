<?php

session_start();

// 1. Check Login 
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: ../Auth/login.php");
    exit();
}

// 2. DB 
require_once __DIR__ . "/../Include/db.php";

// 3. Layout 
include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';

// --- PHÂN QUYỀN: CHẶN NẾU KHÔNG PHẢI QUẢN LÝ ---
$currentUserId = $_SESSION['id_nguoi_dung'] ?? 0;
$stmtCheck = $pdo->prepare("SELECT id_vai_tro FROM nguoi_dung WHERE id_nguoi_dung = ?");
$stmtCheck->execute([$currentUserId]);
$roleId = $stmtCheck->fetchColumn();

if ($roleId != 1) { 
    echo "<div class='container mt-5'><div class='alert alert-danger text-center fw-bold'>
            ⚠️ BẠN KHÔNG CÓ QUYỀN QUẢN LÝ NHÂN SỰ!<br>
            <a href='../Users_Admin/staff.php' class='btn btn-danger btn-sm mt-2'>Quay lại</a>
          </div></div>";
    include __DIR__ . '/../Include/footer.php';
    exit();
}

$error = "";
$success = "";

// Lấy tham số
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';


// 1. XỬ LÝ XÓA (DELETE)

if ($action === 'delete' && $id > 0) {
    try {
        // Kiểm tra ràng buộc
        $checkHD = $pdo->prepare("SELECT 1 FROM hoa_don WHERE id_nhan_vien = ? LIMIT 1");
        $checkHD->execute([$id]);
        
        $checkPN = $pdo->prepare("SELECT 1 FROM phieu_nhap WHERE id_nhan_vien = ? LIMIT 1");
        $checkPN->execute([$id]);

        if ($checkHD->fetchColumn() || $checkPN->fetchColumn()) {
            echo "<script>alert('Không thể xóa nhân viên đã có lịch sử giao dịch! Hãy chuyển trạng thái sang KHÓA.'); window.location.href='../Users_Admin/staff.php';</script>";
        } else {
            $pdo->prepare("DELETE FROM nguoi_dung WHERE id_nguoi_dung = ?")->execute([$id]);
            echo "<script>alert('Xóa nhân viên thành công!'); window.location.href='../Users_Admin/staff.php';</script>";
        }
        exit();

    } catch (Exception $e) {
        $error = "Lỗi xóa: " . $e->getMessage();
    }
}

// 2. CHUẨN BỊ DỮ LIỆU FORM

$isEdit = false;
$userData = [
    'ho_ten' => '', 
    'ten_dang_nhap' => '', 
    'so_dien_thoai' => '', 
    'id_vai_tro' => '', 
    'trang_thai' => 'HOAT_DONG'
];

if ($id > 0 && $action !== 'delete') {
    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE id_nguoi_dung = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $isEdit = true;
        $userData = $row;
    } else {
        $error = "Không tìm thấy nhân viên!";
    }
}

// Lấy danh sách vai trò
$roles = [];
try {
    $roles = $pdo->query("SELECT id_vai_tro, ten_vai_tro FROM vai_tro ORDER BY ten_vai_tro")->fetchAll();
} catch (Exception $e) {}


// 3. XỬ LÝ SUBMIT FORM

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['ho_ten']);
    $user  = trim($_POST['ten_dang_nhap']);
    $phone = trim($_POST['so_dien_thoai']);
    $pass  = $_POST['mat_khau'];
    $role  = $_POST['id_vai_tro'] ?: null;
    $status= $_POST['trang_thai'];

    if ($hoTen === "" || $user === "" || $phone === "") {
        $error = "Vui lòng nhập đủ thông tin (Họ tên, User, SĐT).";
    } elseif (!$isEdit && $pass === "") {
        $error = "Vui lòng nhập mật khẩu cho nhân viên mới.";
    } elseif (!preg_match('/^0\d{9,10}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ.";
    } else {
        try {
            if ($isEdit) {
                // --- UPDATE ---
                // Check trùng user/sđt (trừ chính mình)
                $chk = $pdo->prepare("SELECT 1 FROM nguoi_dung WHERE (ten_dang_nhap=? OR so_dien_thoai=?) AND id_nguoi_dung != ?");
                $chk->execute([$user, $phone, $id]);
                if ($chk->fetchColumn()) {
                    $error = "Tên đăng nhập hoặc SĐT đã tồn tại!";
                } else {
                    if (!empty($pass)) {
                        $hash = password_hash($pass, PASSWORD_BCRYPT);
                        $sql = "UPDATE nguoi_dung SET ho_ten=?, ten_dang_nhap=?, so_dien_thoai=?, id_vai_tro=?, trang_thai=?, mat_khau_hash=? WHERE id_nguoi_dung=?";
                        $pdo->prepare($sql)->execute([$hoTen, $user, $phone, $role, $status, $hash, $id]);
                    } else {
                        $sql = "UPDATE nguoi_dung SET ho_ten=?, ten_dang_nhap=?, so_dien_thoai=?, id_vai_tro=?, trang_thai=? WHERE id_nguoi_dung=?";
                        $pdo->prepare($sql)->execute([$hoTen, $user, $phone, $role, $status, $id]);
                    }
                    $success = "Cập nhật nhân viên thành công!";
                    $userData = $_POST; 
                }
            } else {
                // --- INSERT ---
                $chk = $pdo->prepare("SELECT 1 FROM nguoi_dung WHERE ten_dang_nhap=? OR so_dien_thoai=?");
                $chk->execute([$user, $phone]);
                if ($chk->fetchColumn()) {
                    $error = "Tên đăng nhập hoặc SĐT đã tồn tại!";
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, so_dien_thoai, mat_khau_hash, id_vai_tro, trang_thai) VALUES (?,?,?,?,?,?)";
                    $pdo->prepare($sql)->execute([$hoTen, $user, $phone, $hash, $role, $status]);
                    $success = "Thêm nhân viên mới thành công!";
                    $userData = ['ho_ten'=>'', 'ten_dang_nhap'=>'', 'so_dien_thoai'=>'', 'id_vai_tro'=>'', 'trang_thai'=>'HOAT_DONG'];
                }
            }
        } catch (Exception $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold m-0"><?= $isEdit ? "Sửa Thông Tin Nhân Viên" : "Thêm Nhân Viên Mới" ?></h4>
        <a href="../Users_Admin/staff.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card-custom p-4" style="max-width: 700px;">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Họ tên</label>
                <input type="text" name="ho_ten" class="form-control" value="<?= htmlspecialchars($userData['ho_ten']) ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tên đăng nhập</label>
                    <input type="text" name="ten_dang_nhap" class="form-control" value="<?= htmlspecialchars($userData['ten_dang_nhap']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" class="form-control" value="<?= htmlspecialchars($userData['so_dien_thoai']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Mật khẩu <?= $isEdit ? '(Để trống nếu không đổi)' : '' ?></label>
                <input type="password" name="mat_khau" class="form-control" <?= $isEdit ? '' : 'required' ?>>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Vai trò</label>
                    <select name="id_vai_tro" class="form-select">
                        <option value="">-- Chọn vai trò --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id_vai_tro'] ?>" <?= ($userData['id_vai_tro'] == $r['id_vai_tro']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['ten_vai_tro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="HOAT_DONG" <?= ($userData['trang_thai'] == 'HOAT_DONG') ? 'selected' : '' ?>>HOẠT ĐỘNG</option>
                        <option value="KHOA" <?= ($userData['trang_thai'] == 'KHOA') ? 'selected' : '' ?>>KHÓA</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary rounded-pill px-4 fw-bold mt-3">
                <i class="fas fa-save me-1"></i> <?= $isEdit ? "Lưu Thay Đổi" : "Lưu Nhân Viên" ?>
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../Include/footer.php'; ?>