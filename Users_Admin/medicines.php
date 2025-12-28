<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header("Location: ../Auth/login.php");
    exit();
}

require_once __DIR__ . "/../Include/db.php";

// --- PHÂN QUYỀN ---
$currentUserId = $_SESSION['id_nguoi_dung'] ?? 0;
$isManager = false;
try {
    $stmtRole = $pdo->prepare("SELECT id_vai_tro FROM nguoi_dung WHERE id_nguoi_dung = ?");
    $stmtRole->execute([$currentUserId]);
    if ($stmtRole->fetchColumn() == 1) { 
        $isManager = true; 
    }
} catch (Exception $e) {}

// Ảnh mặc định
$defaultImg = "https://cdn.nhathuoclongchau.com.vn/unsafe/640x0/filters:quality(90):format(webp)/00345353_hair_volume_vien_uong_duong_toc_7952_62af_large_46a5a99e57.jpg";

// Xử lý Tìm kiếm
$keyword = trim($_GET['q'] ?? '');
$params = [];

$sql = "
    SELECT 
        t.id_thuoc,
        t.ma_vach,
        t.ten_thuoc,
        t.dang_bao_che,
        t.gia_ban_de_xuat,
        t.hinh_anh,
        COALESCE(v.tong_ton_kho, 0) AS ton_kho
    FROM thuoc t
    LEFT JOIN vw_ton_kho_theo_thuoc v 
        ON v.id_thuoc = t.id_thuoc
";

if ($keyword !== "") {
    $sql .= " WHERE t.ten_thuoc LIKE ? OR t.ma_vach LIKE ? OR t.dang_bao_che LIKE ? ";
    $searchTerm = "%" . $keyword . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$sql .= " ORDER BY t.ten_thuoc LIMIT 300";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi query medicines: " . $e->getMessage());
}

include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';
?>

<div class="card-custom">
    <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="fw-bold m-0">
            <i class="fas fa-pills me-2 text-primary"></i>
            <?php if($keyword): ?>
                Kết quả tìm kiếm: "<?= htmlspecialchars($keyword) ?>"
            <?php else: ?>
                Danh Sách Thuốc
            <?php endif; ?>
        </h5>

        <?php if ($isManager): ?>
            <a href="../Admin/edit_medicine.php" class="btn btn-primary rounded-pill btn-sm px-3">
              <i class="fas fa-plus"></i> Thêm Mới
            </a>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success m-3"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger m-3"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Mã</th>
                    <th>Ảnh</th>
                    <th>Tên Thuốc</th>
                    <th>Loại</th>
                    <th>Giá Bán</th>
                    <th>Tồn Kho</th>
                    <?php if ($isManager): ?>
                        <th class="text-end pe-4">Hành động</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($medicines)): ?>
                    <tr>
                        <td colspan="<?= $isManager ? 7 : 6 ?>" class="text-center text-muted p-4">
                            Không tìm thấy thuốc nào phù hợp.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($medicines as $m): ?>
                        <?php
                            // Chuẩn hóa dữ liệu
                            $maVach   = htmlspecialchars($m['ma_vach'] ?? '');
                            $tenThuoc = htmlspecialchars($m['ten_thuoc'] ?? '');
                            $loai     = htmlspecialchars($m['dang_bao_che'] ?? '—');
                            $gia      = (float)($m['gia_ban_de_xuat'] ?? 0);
                            $tonKho   = (int)($m['ton_kho'] ?? 0);

                            // Xử lý ảnh
                            $dbImg = trim((string)($m['hinh_anh'] ?? ''));
                            if ($dbImg !== '') {
                                // Nếu là link online (http) thì giữ nguyên, nếu là path cục bộ thì thêm ../
                                if (strpos($dbImg, 'http') === 0) {
                                    $imgSrc = $dbImg;
                                } else {
                                    $imgSrc = "../" . $dbImg;
                                }
                            } else {
                                $imgSrc = $defaultImg;
                            }

                            // Badge tồn kho
                            if ($tonKho <= 0) {
                                $stockClass = "bg-danger";
                                $stockText  = "Hết hàng (0)";
                            } elseif ($tonKho < 20) {
                                $stockClass = "bg-warning text-dark";
                                $stockText  = "Sắp hết ($tonKho)";
                            } else {
                                $stockClass = "bg-success";
                                $stockText  = "Còn hàng ($tonKho)";
                            }
                        ?>

                        <tr>
                            <td>#<?= $maVach ?></td>

                            <td>
                                <img
                                    src="<?= htmlspecialchars($imgSrc) ?>"
                                    class="med-thumb"
                                    alt="<?= $tenThuoc ?>"
                                    onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultImg) ?>';"
                                    style="width:40px; height:40px; object-fit:cover; border-radius:5px;"
                                >
                            </td>

                            <td class="fw-bold"><?= $tenThuoc ?></td>

                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $loai ?>
                                </span>
                            </td>

                            <td class="text-primary fw-bold">
                                <?= number_format($gia) ?> đ
                            </td>

                            <td>
                                <span class="badge <?= $stockClass ?>">
                                    <?= $stockText ?>
                                </span>
                            </td>

                            <?php if ($isManager): ?>
                            <td class="text-end pe-4">
                                <a href="../Admin/edit_medicine.php?id=<?= $m['id_thuoc'] ?>" class="btn btn-sm btn-light border me-1" title="Sửa">
                                    <i class="fas fa-pen text-warning"></i>
                                </a>
                                <a href="../Admin/edit_medicine.php?action=delete&id=<?= $m['id_thuoc'] ?>" 
                                   class="btn btn-sm btn-light border"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa thuốc: <?= $tenThuoc ?>? Mọi lịch sử nhập/xuất liên quan cũng sẽ bị xóa!');" title="Xóa">
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