<?php

session_start();


if (!isset($_SESSION['user_logged_in'])) { 
    header("Location: ../Auth/login.php"); 
    exit(); 
}

require_once __DIR__ . "/../Include/db.php";

// --- CHECK QUYỀN QUẢN LÝ ---
$currentUserId = $_SESSION['id_nguoi_dung'] ?? 0;
$isManager = false;
try {
    $stmtRole = $pdo->prepare("SELECT id_vai_tro FROM nguoi_dung WHERE id_nguoi_dung = ?");
    $stmtRole->execute([$currentUserId]);
    if ($stmtRole->fetchColumn() == 1) { 
        $isManager = true; 
    }
} catch (Exception $e) {}

// Biến thông báo (flash)
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Load danh sách nhân viên
$staff = [];
$stats_total = 0; $stats_active = 0; $stats_locked = 0;

try {
    $stmt = $pdo->query("
        SELECT nd.id_nguoi_dung, nd.ho_ten, nd.so_dien_thoai, nd.ten_dang_nhap, nd.trang_thai, vt.ten_vai_tro
        FROM nguoi_dung nd
        LEFT JOIN vai_tro vt ON vt.id_vai_tro = nd.id_vai_tro
        ORDER BY nd.id_nguoi_dung DESC
        LIMIT 200
    ");
    $staff = $stmt->fetchAll();

    $stats_total = count($staff);
    foreach ($staff as $s) {
        if (($s['trang_thai'] ?? '') === 'HOAT_DONG') $stats_active++;
        if (($s['trang_thai'] ?? '') === 'KHOA') $stats_locked++;
    }
} catch (PDOException $e) {
    $flash_error = "Lỗi load nhân viên: " . $e->getMessage();
}

include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';
?>

<?php if(isset($_GET['msg'])): ?><div class="alert alert-success m-3"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
<?php if(isset($_GET['error'])): ?><div class="alert alert-danger m-3"><?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

<?php if (!empty($flash_success)): ?><div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
<?php if (!empty($flash_error)): ?><div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-custom p-4 d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle"><i class="fas fa-users fa-lg"></i></div>
            <div><h3 class="fw-bold m-0"><?= (int)$stats_total ?></h3><small class="text-muted">Tổng nhân viên</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-4 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle"><i class="fas fa-user-check fa-lg"></i></div>
            <div><h3 class="fw-bold m-0"><?= (int)$stats_active ?></h3><small class="text-muted">Đang hoạt động</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-4 d-flex align-items-center gap-3">
            <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle"><i class="fas fa-user-lock fa-lg"></i></div>
            <div><h3 class="fw-bold m-0"><?= (int)$stats_locked ?></h3><small class="text-muted">Đã khóa</small></div>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="fw-bold m-0 text-dark">Danh Sách Nhân Sự</h5>
        
        <?php if ($isManager): ?>
        <a class="btn btn-primary rounded-pill btn-sm px-3" href="../Admin/edit_user.php">
            <i class="fas fa-user-plus me-2"></i> Thêm Nhân Viên
        </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Nhân Viên</th>
                    <th>Liên Hệ</th>
                    <th>Chức Vụ</th>
                    <th>Trạng Thái</th>
                    <?php if ($isManager): ?><th class="text-end pe-4">Hành động</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($staff)): ?>
                <tr><td colspan="<?= $isManager ? 5 : 4 ?>" class="text-center text-muted p-4">Chưa có nhân viên trong database.</td></tr>
            <?php else: ?>
                <?php foreach ($staff as $s): ?>
                    <?php
                        $id   = (int)($s['id_nguoi_dung'] ?? 0);
                        $name = trim((string)($s['ho_ten'] ?? ''));
                        $phone = trim((string)($s['so_dien_thoai'] ?? ''));
                        $user = trim((string)($s['ten_dang_nhap'] ?? ''));
                        $role = trim((string)($s['ten_vai_tro'] ?? '—'));
                        $status = trim((string)($s['trang_thai'] ?? ''));
                        $avatarName = $name !== '' ? $name : ($user !== '' ? $user : "User");

                        if ($status === 'HOAT_DONG') $statusHtml = '<span class="badge bg-success rounded-pill">Đang hoạt động</span>';
                        elseif ($status === 'KHOA') $statusHtml = '<span class="badge bg-danger rounded-pill">Đã khóa</span>';
                        else $statusHtml = '<span class="badge bg-secondary rounded-pill">—</span>';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($avatarName) ?>&background=00b09b&color=fff" class="med-thumb rounded-circle border-0">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($avatarName) ?></div>
                                    <small class="text-muted">ID: #<?= $id ?> • <?= htmlspecialchars($user) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($phone !== '' ? $phone : '—') ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= htmlspecialchars($role) ?></span></td>
                        <td><?= $statusHtml ?></td>

                        <?php if ($isManager): ?>
                        <td class="text-end pe-4">
                            <a class="btn btn-light btn-sm border" href="../Admin/edit_user.php?id=<?= $id ?>" title="Sửa">
                                <i class="fas fa-edit text-warning"></i>
                            </a>
                            <a class="btn btn-light btn-sm border" href="../Admin/edit_user.php?action=delete&id=<?= $id ?>" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');" title="Xóa">
                                <i class="fas fa-trash text-danger"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../Include/footer.php'; ?>