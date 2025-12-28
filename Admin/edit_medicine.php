<?php
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: ../Auth/login.php");
    exit();
}

// 2. Kết nối DB
require_once __DIR__ . "/../Include/db.php";

// 3. Include layout 
include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';

// --- PHÂN QUYỀN: CHẶN NẾU KHÔNG PHẢI QUẢN LÝ ---
$currentUserId = $_SESSION['id_nguoi_dung'] ?? 0;
$stmtCheck = $pdo->prepare("SELECT id_vai_tro FROM nguoi_dung WHERE id_nguoi_dung = ?");
$stmtCheck->execute([$currentUserId]);
$roleId = $stmtCheck->fetchColumn();

// Giả sử ID = 1 là Quản lý
if ($roleId != 1) {
    echo "<div class='container mt-5'><div class='alert alert-danger text-center fw-bold'>
            ⚠️ BẠN KHÔNG CÓ QUYỀN TRUY CẬP TRANG NÀY!<br>
            <a href='../Users_Admin/medicines.php' class='btn btn-danger btn-sm mt-2'>Quay lại</a>
          </div></div>";
    include __DIR__ . '/../Include/footer.php';
    exit();
}

$error = "";
$success = "";

// Lấy tham số từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

// 1. XỬ LÝ XÓA (DELETE)
if ($action === 'delete' && $id > 0) {
    try {
        $pdo->beginTransaction();

        // Xóa Chi tiết nhập
        $pdo->prepare("DELETE FROM ct_phieu_nhap WHERE id_lo_thuoc IN (SELECT id_lo_thuoc FROM lo_thuoc WHERE id_thuoc = ?)")->execute([$id]);
        
        // Xóa Chi tiết bán 
        try {
            $pdo->prepare("DELETE FROM ct_hoa_don WHERE id_lo_thuoc IN (SELECT id_lo_thuoc FROM lo_thuoc WHERE id_thuoc = ?)")->execute([$id]);
        } catch (Exception $e) {}

        // Xóa Lô thuốc
        $pdo->prepare("DELETE FROM lo_thuoc WHERE id_thuoc = ?")->execute([$id]);

        // Xóa Thuốc
        $pdo->prepare("DELETE FROM thuoc WHERE id_thuoc = ?")->execute([$id]);

        $pdo->commit();
        
        echo "<script>alert('Đã xóa thuốc và toàn bộ lịch sử liên quan!'); window.location.href='../Users_Admin/medicines.php';</script>";
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Lỗi khi xóa: " . $e->getMessage();
    }
}

// 2. CHUẨN BỊ DỮ LIỆU FORM (CHO SỬA HOẶC THÊM)
$isEdit = false;
$formData = [
    'ma_vach' => '', 
    'ten_thuoc' => '', 
    'dang_bao_che' => '', 
    'gia_ban_de_xuat' => '', 
    'hinh_anh' => ''
];

// Nếu có ID -> Chế độ SỬA -> Lấy dữ liệu cũ
if ($id > 0 && $action !== 'delete') {
    $stmt = $pdo->prepare("SELECT * FROM thuoc WHERE id_thuoc = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $isEdit = true;
        $formData = $row;
    } else {
        $error = "Không tìm thấy thuốc cần sửa!";
    }
}


//  Lấy danh sách ảnh trong thư mục Medicine_data/
$imgDir = __DIR__ . "/../Medicine_data";

$allImages = [];
if (is_dir($imgDir)) {
    $allImages = array_merge(
        glob($imgDir . "/*.jpg") ?: [],
        glob($imgDir . "/*.jpeg") ?: [],
        glob($imgDir . "/*.png") ?: [],
        glob($imgDir . "/*.webp") ?: []
    );
}

$allImageRel = [];
foreach ($allImages as $fullPath) {
    // Lưu vào DB dạng: Medicine_data/tenfile.jpg
    $fileName = basename($fullPath);
    $allImageRel[] = "Medicine_data/" . $fileName;
}

// Hàm chọn ảnh random KHÔNG trùng 
function pickRandomImageNotUsed($pdo, $allImageRel) {
    if (empty($allImageRel)) return "";
    $used = [];
    try {
        $stmt = $pdo->query("SELECT hinh_anh FROM thuoc WHERE hinh_anh IS NOT NULL AND hinh_anh <> ''");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $r) $used[] = trim((string)$r);
    } catch (Exception $e) { $used = []; }

    $available = array_values(array_diff($allImageRel, $used));
    if (!empty($available)) return $available[array_rand($available)];
    return $allImageRel[array_rand($allImageRel)];
}


// 3. XỬ LÝ SUBMIT FORM (INSERT HOẶC UPDATE)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ma = trim($_POST['ma_vach'] ?? '');
    $ten = trim($_POST['ten_thuoc'] ?? '');
    $dang = trim($_POST['dang_bao_che'] ?? '');
    $gia = trim($_POST['gia_ban_de_xuat'] ?? '');
    $anh = trim($_POST['hinh_anh'] ?? '');
    
    // Ưu tiên link ảnh online nếu có
    if (!empty($_POST['hinh_anh_url'])) {
        $anh = trim($_POST['hinh_anh_url']);
    }

    if ($ma === "" || $ten === "" || $dang === "" || $gia === "") {
        $error = "Vui lòng nhập đầy đủ: Mã, Tên, Loại, Giá bán.";
    } elseif (!is_numeric($gia) || (float)$gia < 0) {
        $error = "Giá bán phải là số >= 0.";
    } else {
        // Nếu user không chọn ảnh -> Tự random
        if ($anh === "" && !$isEdit) {
            $anh = pickRandomImageNotUsed($pdo, $allImageRel);
        }

        try {
            if ($isEdit) {
                // --- UPDATE ---
                // Check trùng mã vạch với thuốc khác
                $chk = $pdo->prepare("SELECT 1 FROM thuoc WHERE ma_vach = ? AND id_thuoc != ?");
                $chk->execute([$ma, $id]);
                if ($chk->fetchColumn()) {
                    $error = "Mã vạch này đã tồn tại ở thuốc khác!";
                } else {
                    $sql = "UPDATE thuoc SET ma_vach=?, ten_thuoc=?, dang_bao_che=?, gia_ban_de_xuat=?, hinh_anh=? WHERE id_thuoc=?";
                    $pdo->prepare($sql)->execute([$ma, $ten, $dang, (float)$gia, $anh, $id]);
                    $success = "Cập nhật thuốc thành công!";
                    // Refresh data
                    $formData = ['ma_vach'=>$ma, 'ten_thuoc'=>$ten, 'dang_bao_che'=>$dang, 'gia_ban_de_xuat'=>$gia, 'hinh_anh'=>$anh];
                }
            } else {
                // --- INSERT ---
                $chk = $pdo->prepare("SELECT 1 FROM thuoc WHERE ma_vach = ? LIMIT 1");
                $chk->execute([$ma]);
                if ($chk->fetchColumn()) {
                    $error = "Mã vạch đã tồn tại. Hãy nhập mã khác.";
                } else {
                    $sql = "INSERT INTO thuoc (ma_vach, ten_thuoc, dang_bao_che, gia_ban_de_xuat, hinh_anh) VALUES (?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([$ma, $ten, $dang, (float)$gia, $anh]);
                    $success = "Thêm thuốc mới thành công!";
                    // Reset form
                    $formData = ['ma_vach'=>'', 'ten_thuoc'=>'', 'dang_bao_che'=>'', 'gia_ban_de_xuat'=>'', 'hinh_anh'=>''];
                }
            }
        } catch (PDOException $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold m-0"><?= $isEdit ? "Chỉnh Sửa Thuốc" : "Thêm Thuốc Mới" ?></h4>
        <a href="../Users_Admin/medicines.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card-custom p-4" style="max-width: 900px;">
        <form method="POST">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-bold">Mã (mã vạch)</label>
                    <input type="text" name="ma_vach" class="form-control"
                           value="<?= htmlspecialchars($formData['ma_vach']) ?>" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-bold">Tên thuốc</label>
                    <input type="text" name="ten_thuoc" class="form-control"
                           value="<?= htmlspecialchars($formData['ten_thuoc']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Loại (dạng bào chế)</label>
                    <input type="text" name="dang_bao_che" class="form-control"
                           placeholder="VD: Viên nén..."
                           value="<?= htmlspecialchars($formData['dang_bao_che']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Giá bán đề xuất</label>
                    <input type="number" name="gia_ban_de_xuat" class="form-control" min="0"
                           value="<?= htmlspecialchars($formData['gia_ban_de_xuat']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Ảnh (tuỳ chọn)</label>
                    
                    <select name="hinh_anh" class="form-select mb-2" onchange="if(this.value) document.getElementsByName('hinh_anh_url')[0].value=''">
                        <option value="">-- Tự random ảnh (khuyên dùng) --</option>
                        <?php foreach ($allImageRel as $img): ?>
                            <option value="<?= htmlspecialchars($img) ?>"
                                <?= ($formData['hinh_anh'] === $img) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($img) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" name="hinh_anh_url" class="form-control form-control-sm" 
                           value="<?= (strpos($formData['hinh_anh'], 'http') === 0) ? htmlspecialchars($formData['hinh_anh']) : '' ?>" 
                           placeholder="Hoặc dán link ảnh online..." 
                           onchange="document.getElementsByName('hinh_anh')[0].value=''">
                           
                    <div class="form-text">
                        Nếu không chọn, hệ thống sẽ tự gán ảnh random (ưu tiên không trùng).
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <button class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fas fa-save me-1"></i> <?= $isEdit ? "Lưu Thay Đổi" : "Lưu Thuốc Mới" ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../Include/footer.php'; ?>