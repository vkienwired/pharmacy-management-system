<?php

session_start();

if (!isset($_SESSION['user_logged_in'])) { 
    header("Location: ../Auth/login.php"); 
    exit(); 
}

require_once __DIR__ . "/../Include/db.php";


// Helpers

function getTableColumns(PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cols = [];
        foreach ($rows as $r) { $cols[$r['Field']] = $r; }
        return $cols;
    } catch (Exception $e) { return []; }
}

$flash_success = "";
$flash_error   = "";


// Nhận filter từ URL

$supplier_id = trim($_GET['supplier_id'] ?? '');
$date_filter = trim($_GET['date_filter'] ?? '');
$view_id     = trim($_GET['view_id'] ?? '');


// 1) XỬ LÝ TẠO PHIẾU NHẬP (POST)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_import') {

    $id_cong_ty = trim($_POST['id_cong_ty_cung_cap'] ?? '');
    $ghiChu     = trim($_POST['ghi_chu'] ?? '');
    
    // Nhận mảng input
    $arr_id      = $_POST['id_thuoc'] ?? [];
    $arr_sl      = $_POST['so_luong_nhap'] ?? [];
    $arr_gia_ban = $_POST['gia_ban_moi'] ?? []; 
    $arr_gia_nhap= $_POST['don_gia_nhap'] ?? []; 
    $arr_solo    = $_POST['so_lo'] ?? [];
    
    $hsd_default = '2099-12-31';

    if ($id_cong_ty === "") {
        $flash_error = "Vui lòng chọn nhà cung cấp.";
    } else {
        try {
            $username = $_SESSION['username'] ?? '';
            $stmtNV = $pdo->prepare("SELECT id_nguoi_dung FROM nguoi_dung WHERE ten_dang_nhap=?");
            $stmtNV->execute([$username]);
            $idNhanVien = (int)$stmtNV->fetchColumn() ?: ($_SESSION['id_nguoi_dung']??0);

            $pdo->beginTransaction();

            // 1. Tạo Header Phiếu Nhập
            $maPhieu = "PN" . date("YmdHis") . rand(10, 99);
            $stmtPN = $pdo->prepare("INSERT INTO phieu_nhap (ma_phieu_nhap, ngay_nhap, id_cong_ty_cung_cap, id_nhan_vien, tong_tien, ghi_chu) VALUES (?, NOW(), ?, ?, 0, ?)");
            $stmtPN->execute([$maPhieu, $id_cong_ty, $idNhanVien, $ghiChu]);
            $idPhieu = $pdo->lastInsertId();

            $tongTien = 0;
            
            // Prepare Statements
            $stmtLo = $pdo->prepare("INSERT INTO lo_thuoc (id_thuoc, so_lo, han_su_dung, so_luong_ton, gia_nhap, id_cong_ty_cung_cap, ngay_nhap) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmtCT = $pdo->prepare("INSERT INTO ct_phieu_nhap (id_phieu_nhap, id_lo_thuoc, so_luong_nhap, don_gia_nhap, thanh_tien) VALUES (?, ?, ?, ?, ?)");
            $stmtUpdPrice = $pdo->prepare("UPDATE thuoc SET gia_ban_de_xuat = ? WHERE id_thuoc = ?");

            for($i=0; $i<count($arr_id); $i++) {
                $id_thuoc = (int)$arr_id[$i];
                $sl       = (int)$arr_sl[$i];
                $gia_ban  = (float)$arr_gia_ban[$i];
                $gia_nhap = (float)$arr_gia_nhap[$i];
                $so_lo    = trim($arr_solo[$i]) ?: ($maPhieu."-".($i+1));

                if($id_thuoc>0 && $sl>0) {
                    $tt = $sl * $gia_nhap;
                    $tongTien += $tt;
                    
                    // Tạo lô mới
                    $stmtLo->execute([$id_thuoc, $so_lo, $hsd_default, $sl, $gia_nhap, $id_cong_ty]);
                    $idLo = $pdo->lastInsertId();
                    
                    // Lưu chi tiết
                    $stmtCT->execute([$idPhieu, $idLo, $sl, $gia_nhap, $tt]);

                    // Cập nhật giá bán vào bảng thuốc
                    if($gia_ban > 0) {
                        $stmtUpdPrice->execute([$gia_ban, $id_thuoc]);
                    }
                }
            }

            // 3. Update tổng tiền
            $pdo->prepare("UPDATE phieu_nhap SET tong_tien=? WHERE id_phieu_nhap=?")->execute([$tongTien, $idPhieu]);
            
            $pdo->commit();
            header("Location: imports.php?created=1"); exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $flash_error = "Lỗi nhập hàng: " . $e->getMessage();
        }
    }
}

if (($_GET['created'] ?? '') == '1') {
    $flash_success = "Nhập hàng thành công! Đã cập nhật giá bán mới.";
}


include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';


// LOAD DỮ LIỆU HIỂN THỊ
$nccs = [];
try {
    $nccs = $pdo->query("SELECT * FROM cong_ty WHERE loai_cong_ty='NHA_CUNG_CAP'")->fetchAll();
} catch (Exception $e) { $nccs = []; }

$meds = [];
try {
    $stmtM = $pdo->query("SELECT id_thuoc, ten_thuoc, gia_ban_de_xuat FROM thuoc ORDER BY ten_thuoc");
    $meds = $stmtM->fetchAll();
} catch (Exception $e) { $meds = []; }

$imports = [];
$error_imports = "";
try {
    $where = []; $params = [];
    if ($supplier_id !== "") { $where[] = "pn.id_cong_ty_cung_cap = ?"; $params[] = $supplier_id; }
    if ($date_filter !== "") { $where[] = "DATE(pn.ngay_nhap) = ?"; $params[] = $date_filter; }
    $whereSql = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";

    $sql = "SELECT pn.id_phieu_nhap, pn.ma_phieu_nhap, pn.ngay_nhap, pn.tong_tien, c.ten_cong_ty, COALESCE(SUBSTRING_INDEX(GROUP_CONCAT(CONCAT(t.ten_thuoc, ' (', ctpn.so_luong_nhap, ')') SEPARATOR ', '), ', ', 3), '') AS items_preview, COUNT(ctpn.id_ct_phieu_nhap) as so_dong FROM phieu_nhap pn LEFT JOIN cong_ty c ON pn.id_cong_ty_cung_cap = c.id_cong_ty LEFT JOIN ct_phieu_nhap ctpn ON ctpn.id_phieu_nhap = pn.id_phieu_nhap LEFT JOIN lo_thuoc lt ON lt.id_lo_thuoc = ctpn.id_lo_thuoc LEFT JOIN thuoc t ON t.id_thuoc = lt.id_thuoc $whereSql GROUP BY pn.id_phieu_nhap, pn.ma_phieu_nhap, pn.ngay_nhap, pn.tong_tien, c.ten_cong_ty ORDER BY pn.ngay_nhap DESC LIMIT 50";
    $stmtS = $pdo->prepare($sql);
    $stmtS->execute($params);
    $imports = $stmtS->fetchAll();
} catch (Exception $e) { $error_imports = $e->getMessage(); }

$tongNhapNamNay = 0;
try {
    $stmtSum = $pdo->query("SELECT SUM(tong_tien) FROM phieu_nhap WHERE YEAR(ngay_nhap) = YEAR(CURDATE())");
    $tongNhapNamNay = (float)$stmtSum->fetchColumn();
} catch (Exception $e) {}

$view_header = null; $view_details = []; $view_error = "";
if ($view_id !== "") {
    try {
        $stmtH = $pdo->prepare("SELECT pn.*, c.ten_cong_ty FROM phieu_nhap pn LEFT JOIN cong_ty c ON pn.id_cong_ty_cung_cap = c.id_cong_ty WHERE pn.id_phieu_nhap = ?");
        $stmtH->execute([$view_id]);
        $view_header = $stmtH->fetch();
        $stmtD = $pdo->prepare("SELECT t.ten_thuoc, ctpn.so_luong_nhap, ctpn.don_gia_nhap, ctpn.thanh_tien FROM ct_phieu_nhap ctpn JOIN lo_thuoc lt ON ctpn.id_lo_thuoc = lt.id_lo_thuoc JOIN thuoc t ON lt.id_thuoc = t.id_thuoc WHERE ctpn.id_phieu_nhap = ?");
        $stmtD->execute([$view_id]);
        $view_details = $stmtD->fetchAll();
    } catch (Exception $e) { $view_error = $e->getMessage(); }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><i class="fas fa-truck-loading me-2 text-primary"></i>Lịch Sử Nhập Kho</h4>
    <button class="btn btn-primary rounded-pill shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="fas fa-plus-circle me-2"></i> Tạo Phiếu Nhập
    </button>
</div>

<?php if ($flash_success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>

<?php if ($view_id !== ""): ?>
    <div class="card-custom mb-4">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold fs-5">Chi tiết phiếu nhập</div>
                <?php if ($view_header): ?>
                    <div class="text-muted small">
                        Mã: <b>#<?= htmlspecialchars($view_header['ma_phieu_nhap'] ?? '') ?></b> | 
                        NCC: <b><?= htmlspecialchars($view_header['ten_cong_ty'] ?? '---') ?></b> | 
                        Ngày: <b><?= date("d/m/Y H:i", strtotime($view_header['ngay_nhap'])) ?></b> | 
                        Tổng: <b><?= number_format($view_header['tong_tien']) ?> đ</b>
                    </div>
                <?php endif; ?>
            </div>
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="imports.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div class="p-4">
            <?php if ($view_error): ?>
                <div class="alert alert-danger">Lỗi: <?= htmlspecialchars($view_error) ?></div>
            <?php elseif (!$view_header): ?>
                <div class="alert alert-warning">Không tìm thấy phiếu nhập.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Tên thuốc</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($view_details as $d): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($d['ten_thuoc']) ?></td>
                                    <td><?= number_format($d['so_luong_nhap']) ?></td>
                                    <td><?= number_format($d['don_gia_nhap']) ?> đ</td>
                                    <td class="fw-bold text-primary"><?= number_format($d['thanh_tien']) ?> đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card-custom mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <form class="d-flex gap-2" method="GET">
            <select name="supplier_id" class="form-select form-select-sm w-auto rounded-pill bg-light border-0 fw-bold">
                <option value="">Tất cả NCC</option>
                <?php foreach ($nccs as $n): ?>
                    <option value="<?= $n['id_cong_ty'] ?>" <?= $supplier_id == $n['id_cong_ty'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($n['ten_cong_ty']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date_filter" class="form-control form-control-sm w-auto rounded-pill bg-light border-0" 
                   value="<?= htmlspecialchars($date_filter) ?>">

            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                <i class="fas fa-filter me-1"></i> Lọc
            </button>
            <a href="imports.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold">
                <i class="fas fa-rotate-left me-1"></i> Reset
            </a>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Mã Phiếu</th>
                    <th>Ngày Nhập</th>
                    <th>Nhà Cung Cấp</th>
                    <th width="30%">Chi Tiết (Tóm tắt)</th>
                    <th>Tổng Tiền</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($error_imports): ?>
                    <tr><td colspan="6" class="p-4 text-center text-danger"><?= $error_imports ?></td></tr>
                <?php elseif (empty($imports)): ?>
                    <tr><td colspan="6" class="text-center text-muted p-4">Chưa có phiếu nhập nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($imports as $row): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?= htmlspecialchars($row['ma_phieu_nhap']) ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($row['ngay_nhap'])) ?></td>
                            <td><?= htmlspecialchars($row['ten_cong_ty'] ?? '---') ?></td>
                            <td class="text-muted small">
                                <?= htmlspecialchars($row['items_preview']) ?> 
                                <?= $row['so_dong'] > 3 ? '...' : '' ?>
                            </td>
                            <td class="fw-bold"><?= number_format($row['tong_tien']) ?> đ</td>
                            <td class="text-end pe-4">
                                <a href="imports.php?view_id=<?= $row['id_phieu_nhap'] ?>" class="btn btn-sm btn-light border">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card p-3 border-0 shadow-sm d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle"><i class="fas fa-money-bill-wave fa-lg"></i></div>
            <div>
                <h5 class="fw-bold m-0"><?= number_format($tongNhapNamNay) ?> đ</h5>
                <small class="text-muted">Tổng nhập năm nay</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tạo Phiếu Nhập Kho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_import">
                
                <div class="modal-body bg-light">
                    <div class="card p-3 border-0 mb-3">
                        <h6 class="fw-bold text-primary mb-3">Thông Tin Chung</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Ngày nhập (Tĩnh)</label>
                                <input type="text" id="importTime" class="form-control fw-bold text-primary" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nhà cung cấp</label>
                                <select name="id_cong_ty_cung_cap" class="form-select" required>
                                    <option value="">-- Chọn --</option>
                                    <?php foreach($nccs as $n) echo "<option value='{$n['id_cong_ty']}'>{$n['ten_cong_ty']}</option>"; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Ghi chú</label>
                                <input type="text" name="ghi_chu" class="form-control" placeholder="Ví dụ: Nhập hàng đợt 1...">
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 border-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-primary m-0">Chi Tiết Hàng Hóa</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="btnAddRow">
                                <i class="fas fa-plus me-1"></i> Thêm dòng
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle bg-white mb-2">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Thuốc</th>
                                        <th style="width:120px">Số Lô</th>
                                        <th style="width:100px">SL</th>
                                        <th style="width:150px">Giá Bán Mới</th>
                                        <th style="width:150px">Giá Nhập (50%)</th>
                                        <th style="width:150px">Thành tiền</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr>
                                        <td>
                                            <select class="form-select med-select" name="id_thuoc[]" required onchange="updatePrices(this)">
                                                <option value="" data-price="0">-- Chọn thuốc --</option>
                                                <?php foreach ($meds as $m): ?>
                                                    <option value="<?= htmlspecialchars($m['id_thuoc']) ?>" 
                                                            data-price="<?= $m['gia_ban_de_xuat'] ?>">
                                                        <?= htmlspecialchars($m['ten_thuoc']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="so_lo[]" class="form-control" placeholder="Auto"></td>
                                        <td><input type="number" class="form-control qty" name="so_luong_nhap[]" min="1" value="1" required oninput="calcRow(this)"></td>
                                        <td>
                                            <input type="number" class="form-control sell-price" name="gia_ban_moi[]" min="0" value="0" required oninput="syncImportPrice(this)">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control import-price" name="don_gia_nhap[]" min="0" value="0" readonly>
                                        </td>
                                        <td class="text-end fw-bold row-total">0</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light border btnDelRow">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fw-bold">
                            Tổng tiền (tạm tính): <span id="grandTotal">0</span> đ
                        </div>
                        <div class="text-muted small text-end">Giá nhập tự động tính bằng 50% giá bán mới.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-save me-1"></i> Lưu Phiếu Nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Format tiền tệ VN
function formatMoney(amount) {
    return amount.toLocaleString('vi-VN');
}

// Khi chọn thuốc -> Điền giá bán -> Tự tính giá nhập
function updatePrices(sel) {
    const row = sel.closest('tr');
    const opt = sel.options[sel.selectedIndex];
    const currentSellPrice = parseFloat(opt.getAttribute('data-price')) || 0;
    
    // Điền giá bán
    row.querySelector('.sell-price').value = currentSellPrice;
    
    // Tính giá nhập = 50%
    const importPrice = currentSellPrice / 2;
    row.querySelector('.import-price').value = importPrice;
    
    calcRow(sel); // Tính lại tổng dòng
}

// Khi sửa giá bán -> Tính lại giá nhập
function syncImportPrice(input) {
    const row = input.closest('tr');
    const sellPrice = parseFloat(input.value) || 0;
    const importPrice = sellPrice / 2;
    
    row.querySelector('.import-price').value = importPrice;
    calcRow(input);
}

// Tính tiền từng dòng
function calcRow(element) {
    const row = element.closest('tr');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.import-price').value) || 0; // Tính theo giá nhập
    
    const total = qty * price;
    row.querySelector('.row-total').innerText = formatMoney(total);
    calcGrandTotal();
}

// Tính tổng phiếu
function calcGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const txt = tr.querySelector('.row-total').innerText.replace(/\./g, ''); // Xóa dấu chấm
        grandTotal += parseFloat(txt) || 0;
    });
    document.getElementById('grandTotal').innerText = formatMoney(grandTotal);
}

// Thêm dòng mới
document.getElementById('btnAddRow')?.addEventListener('click', function() {
    const tbody = document.getElementById('itemsBody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);
    
    // Reset giá trị
    newRow.querySelector('select').value = "";
    newRow.querySelector('.qty').value = 1;
    newRow.querySelector('.sell-price').value = 0;
    newRow.querySelector('.import-price').value = 0;
    newRow.querySelector('.row-total').innerText = "0";
    newRow.querySelector('input[name="so_lo[]"]').value = ""; // Reset số lô
    
    tbody.appendChild(newRow);
});

// Xóa dòng
document.addEventListener('click', function(e) {
    if (e.target.closest('.btnDelRow')) {
        const tbody = document.getElementById('itemsBody');
        if (tbody.querySelectorAll('tr').length > 1) {
            e.target.closest('tr').remove();
            calcGrandTotal();
        } else {
            alert("Phải có ít nhất 1 dòng thuốc.");
        }
    }
});


// LOGIC THỜI GIAN TĨNH 

const importModal = document.getElementById('importModal');
if (importModal) {
    // Sự kiện 'show.bs.modal' kích hoạt ngay khi bấm nút mở modal
    importModal.addEventListener('show.bs.modal', function () {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const y = now.getFullYear();
        const H = String(now.getHours()).padStart(2, '0');
        const i = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        
        // Điền vào ô input và ĐỨNG YÊN
        document.getElementById('importTime').value = `${d}/${m}/${y} ${H}:${i}:${s}`;
    });
}
</script>

<?php include __DIR__ . '/../Include/footer.php'; ?>