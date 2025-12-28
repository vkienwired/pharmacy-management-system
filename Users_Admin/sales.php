<?php

session_start();

if (!isset($_SESSION['user_logged_in'])) { 
    header("Location: ../Auth/login.php"); 
    exit(); 
}

require_once __DIR__ . "/../Include/db.php";


function getTableColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cols = [];
    foreach ($rows as $r) {
        $cols[$r['Field']] = $r; 
    }
    return $cols;
}


// Thông báo UI

$flash_success = "";
$flash_error   = "";


// Nhận filter từ URL

$staff_id    = trim($_GET['staff_id'] ?? '');
$ngay_ban    = trim($_GET['ngay_ban'] ?? '');
$view_id     = trim($_GET['view_id'] ?? '');


// 1) XỬ LÝ TẠO HÓA ĐƠN (POST)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_sale') {

    // Lấy dữ liệu form
    $ghiChu     = trim($_POST['ghi_chu'] ?? '');
    
    $arr_id_thuoc = $_POST['id_thuoc'] ?? [];
    $arr_so_luong = $_POST['so_luong_ban'] ?? [];
    
    // Gom nhóm số lượng
    $items = [];
    for ($i = 0; $i < count($arr_id_thuoc); $i++) {
        $id = (int)($arr_id_thuoc[$i] ?? 0);
        $qty = (int)($arr_so_luong[$i] ?? 0);
        if ($id > 0 && $qty > 0) {
            if (!isset($items[$id])) $items[$id] = 0;
            $items[$id] += $qty;
        }
    }

    if (empty($items)) {
        $flash_error = "Vui lòng chọn thuốc.";
    } else {
        try {
            $username = $_SESSION['username'] ?? '';
            $stmtNV = $pdo->prepare("SELECT id_nguoi_dung FROM nguoi_dung WHERE ten_dang_nhap = ? LIMIT 1");
            $stmtNV->execute([$username]);
            $idNhanVien = (int)$stmtNV->fetchColumn();
            if ($idNhanVien <= 0) $idNhanVien = $_SESSION['id_nguoi_dung'] ?? 0;

            $pdo->beginTransaction();

            // Insert Header
            $maHD = "HD" . date("YmdHis") . rand(10, 99);
            $stmtHD = $pdo->prepare("
                INSERT INTO hoa_don 
                    (ma_hoa_don, thoi_gian, id_nhan_vien, tong_tien, ghi_chu)
                VALUES 
                    (?, NOW(), ?, 0, ?)
            ");
            $stmtHD->execute([$maHD, $idNhanVien, $ghiChu]);
            $idHoaDon = (int)$pdo->lastInsertId();

            $tongTien = 0;

            // Prepare Statements cho vòng lặp
            $stmtPrice = $pdo->prepare("SELECT gia_ban_de_xuat FROM thuoc WHERE id_thuoc = ?");
            $stmtLots  = $pdo->prepare("SELECT id_lo_thuoc, so_luong_ton FROM lo_thuoc WHERE id_thuoc = ? AND so_luong_ton > 0 ORDER BY id_lo_thuoc ASC");
            $stmtUpLo  = $pdo->prepare("UPDATE lo_thuoc SET so_luong_ton = so_luong_ton - ? WHERE id_lo_thuoc = ?");
            $stmtCT    = $pdo->prepare("INSERT INTO ct_hoa_don (id_hoa_don, id_lo_thuoc, so_luong_ban, don_gia_ban, thanh_tien) VALUES (?, ?, ?, ?, ?)");

            foreach ($items as $id_thuoc => $qtyNeed) {
                // Lấy giá
                $stmtPrice->execute([$id_thuoc]);
                $donGia = (float)$stmtPrice->fetchColumn();

                // Lấy lô (FIFO)
                $stmtLots->execute([$id_thuoc]);
                $lots = $stmtLots->fetchAll();

                $remain = $qtyNeed;
                foreach ($lots as $lot) {
                    if ($remain <= 0) break;
                    
                    $stock = (int)$lot['so_luong_ton'];
                    $take = min($remain, $stock);

                    // Trừ kho
                    $stmtUpLo->execute([$take, $lot['id_lo_thuoc']]);

                    // Lưu chi tiết
                    $tt = $take * $donGia;
                    $stmtCT->execute([$idHoaDon, $lot['id_lo_thuoc'], $take, $donGia, $tt]);

                    $tongTien += $tt;
                    $remain -= $take;
                }

                if ($remain > 0) throw new Exception("Thuốc ID $id_thuoc không đủ tồn kho (Thiếu $remain).");
            }

            // Update tổng tiền
            $pdo->prepare("UPDATE hoa_don SET tong_tien = ? WHERE id_hoa_don = ?")->execute([$tongTien, $idHoaDon]);

            $pdo->commit();
            // Link này giữ nguyên sales.php vì đang ở cùng thư mục
            header("Location: sales.php?created=1");
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $flash_error = "Lỗi bán hàng: " . $e->getMessage();
        }
    }
}

if (($_GET['created'] ?? '') == '1') {
    $flash_success = "Tạo hóa đơn thành công!";
}


include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';


// 2) LOAD DỮ LIỆU HIỂN THỊ


// 2.1) Nhân viên (cho bộ lọc)
$staffs = [];
try {
    $staffs = $pdo->query("SELECT id_nguoi_dung, ho_ten FROM nguoi_dung ORDER BY ho_ten")->fetchAll();
} catch (PDOException $e) { $staffs = []; }

// 2.2) Thuốc (cho modal)
$meds = [];
try {
    $stmtM = $pdo->query("
        SELECT t.id_thuoc, t.ten_thuoc, t.gia_ban_de_xuat,
               COALESCE((SELECT SUM(so_luong_ton) FROM lo_thuoc WHERE id_thuoc=t.id_thuoc), 0) as ton_kho
        FROM thuoc t HAVING ton_kho > 0 ORDER BY t.ten_thuoc
    ");
    $meds = $stmtM->fetchAll();
} catch (PDOException $e) { $meds = []; }

// 2.3) Danh sách hóa đơn
$sales = [];
$error_sales = "";
try {
    $where = []; $params = [];
    if ($staff_id !== "") { $where[] = "hd.id_nhan_vien = ?"; $params[] = $staff_id; }
    if ($ngay_ban !== "") { $where[] = "DATE(hd.thoi_gian) = ?"; $params[] = $ngay_ban; }
    $whereSql = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";

    $sql = "
        SELECT 
            hd.id_hoa_don, hd.ma_hoa_don, hd.thoi_gian, hd.tong_tien, nd.ho_ten,
            COALESCE(SUBSTRING_INDEX(GROUP_CONCAT(CONCAT(t.ten_thuoc, ' (', cthd.so_luong_ban, ')') SEPARATOR ', '), ', ', 3), '') AS items_preview,
            COUNT(cthd.id_ct_hoa_don) AS so_dong
        FROM hoa_don hd
        LEFT JOIN nguoi_dung nd ON hd.id_nhan_vien = nd.id_nguoi_dung
        LEFT JOIN ct_hoa_don cthd ON cthd.id_hoa_don = hd.id_hoa_don
        LEFT JOIN lo_thuoc lt ON lt.id_lo_thuoc = cthd.id_lo_thuoc
        LEFT JOIN thuoc t ON t.id_thuoc = lt.id_thuoc
        $whereSql
        GROUP BY hd.id_hoa_don, hd.ma_hoa_don, hd.thoi_gian, hd.tong_tien, nd.ho_ten
        ORDER BY hd.thoi_gian DESC LIMIT 50
    ";
    $stmtS = $pdo->prepare($sql);
    $stmtS->execute($params);
    $sales = $stmtS->fetchAll();
} catch (PDOException $e) { $error_sales = $e->getMessage(); }

// 2.4) Tổng doanh thu hôm nay
$tongDoanhThu = 0;
try {
    $stmtSum = $pdo->query("SELECT SUM(tong_tien) FROM hoa_don WHERE DATE(thoi_gian) = CURDATE()");
    $tongDoanhThu = (float)$stmtSum->fetchColumn();
} catch (PDOException $e) { $tongDoanhThu = 0; }

// 2.5) Xem chi tiết
$view_header = null; $view_details = []; $view_error = "";
if ($view_id !== "") {
    try {
        $stmtH = $pdo->prepare("SELECT hd.*, nd.ho_ten FROM hoa_don hd LEFT JOIN nguoi_dung nd ON hd.id_nhan_vien = nd.id_nguoi_dung WHERE hd.id_hoa_don = ?");
        $stmtH->execute([$view_id]);
        $view_header = $stmtH->fetch();

        $stmtD = $pdo->prepare("SELECT t.ten_thuoc, cthd.so_luong_ban, cthd.don_gia_ban, cthd.thanh_tien FROM ct_hoa_don cthd JOIN lo_thuoc lt ON cthd.id_lo_thuoc = lt.id_lo_thuoc JOIN thuoc t ON lt.id_thuoc = t.id_thuoc WHERE cthd.id_hoa_don = ?");
        $stmtD->execute([$view_id]);
        $view_details = $stmtD->fetchAll();
    } catch (PDOException $e) { $view_error = $e->getMessage(); }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold m-0"><i class="fas fa-history me-2 text-primary"></i>Lịch Sử Bán Hàng</h4>
    <button class="btn btn-primary rounded-pill shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#saleModal">
        <i class="fas fa-plus-circle me-2"></i> Tạo Hóa Đơn
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
                <div class="fw-bold fs-5">Chi tiết hóa đơn</div>
                <?php if ($view_header): ?>
                    <div class="text-muted small">
                        Mã: <b>#<?= htmlspecialchars($view_header['ma_hoa_don'] ?? '') ?></b> | 
                        NV: <b><?= htmlspecialchars($view_header['ho_ten'] ?? '---') ?></b> | 
                        Ngày: <b><?= date("d/m/Y H:i", strtotime($view_header['thoi_gian'])) ?></b> | 
                        Tổng: <b><?= number_format($view_header['tong_tien']) ?> đ</b>
                    </div>
                <?php endif; ?>
            </div>
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="sales.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
        <div class="p-4">
            <?php if ($view_error): ?>
                <div class="alert alert-danger">Lỗi: <?= htmlspecialchars($view_error) ?></div>
            <?php elseif (!$view_header): ?>
                <div class="alert alert-warning">Không tìm thấy hóa đơn.</div>
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
                                    <td><?= number_format($d['so_luong_ban']) ?></td>
                                    <td><?= number_format($d['don_gia_ban']) ?> đ</td>
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
        <form class="d-flex gap-2" method="GET" action="">
            <select name="staff_id" class="form-select form-select-sm w-auto rounded-pill bg-light border-0 fw-bold">
                <option value="">Tất cả nhân viên</option>
                <?php foreach ($staffs as $s): ?>
                    <option value="<?= htmlspecialchars($s['id_nguoi_dung']) ?>" <?= ($staff_id == $s['id_nguoi_dung']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['ho_ten']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="ngay_ban" class="form-control form-control-sm w-auto rounded-pill bg-light border-0"
                   value="<?= htmlspecialchars($ngay_ban) ?>">

            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                <i class="fas fa-filter me-1"></i> Lọc
            </button>

            <a class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" href="sales.php">
                <i class="fas fa-rotate-left me-1"></i> Reset
            </a>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Mã Hóa Đơn</th>
                    <th>Thời Gian</th>
                    <th>Nhân Viên</th>
                    <th width="30%">Chi Tiết</th>
                    <th>Tổng Tiền</th>
                    <th class="text-end pe-4">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($error_sales): ?>
                    <tr><td colspan="6" class="p-4 text-center text-danger"><?= $error_sales ?></td></tr>
                <?php elseif (empty($sales)): ?>
                    <tr><td colspan="6" class="text-center text-muted p-4">Chưa có giao dịch.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $r): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#<?= htmlspecialchars($r['ma_hoa_don']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['thoi_gian'])) ?></td>
                            <td><?= htmlspecialchars($r['ho_ten']) ?></td>
                            <td class="text-muted small">
                                <?= htmlspecialchars($r['items_preview']) ?>
                                <?= ($r['so_dong'] > 3) ? '...' : '' ?>
                            </td>
                            <td class="fw-bold"><?= number_format($r['tong_tien']) ?> đ</td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-light border" href="sales.php?view_id=<?= urlencode($r['id_hoa_don']) ?>">
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
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle"><i class="fas fa-coins fa-lg"></i></div>
            <div>
                <h5 class="fw-bold m-0"><?= number_format($tongDoanhThu) ?> đ</h5>
                <small class="text-muted">Doanh thu hôm nay</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="saleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Tạo Hóa Đơn Bán Hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create_sale">

                <div class="modal-body bg-light">
                    <div class="card p-3 border-0 mb-3">
                        <h6 class="fw-bold text-primary mb-3">Thông Tin Chung</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Ngày bán (Tĩnh)</label>
                                <input type="text" id="saleTime" class="form-control text-primary fw-bold" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Ghi chú</label>
                                <input type="text" class="form-control" name="ghi_chu" placeholder="Ví dụ: Khách lẻ...">
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
                                        <th>Thuốc (Tồn kho)</th>
                                        <th style="width:140px">Số lượng</th>
                                        <th style="width:180px">Đơn giá bán</th>
                                        <th style="width:180px">Thành tiền</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr>
                                        <td>
                                            <select class="form-select med-select" name="id_thuoc[]" required onchange="updateRow(this)">
                                                <option value="" data-price="0">-- Chọn thuốc --</option>
                                                <?php foreach ($meds as $m): ?>
                                                    <option value="<?= htmlspecialchars($m['id_thuoc']) ?>"
                                                            data-price="<?= $m['gia_ban_de_xuat'] ?>"
                                                            data-stock="<?= $m['ton_kho'] ?>">
                                                        <?= htmlspecialchars($m['ten_thuoc']) ?> (Tồn: <?= $m['ton_kho'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" class="form-control qty" name="so_luong_ban[]" min="1" value="1" required oninput="calcRow(this)"></td>
                                        <td><input type="text" class="form-control price-display" value="0" readonly disabled></td>
                                        <td class="text-end fw-bold row-total">0</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light border btnDelRow" title="Xóa">
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
                        <div class="text-muted small">Tổng tiền sẽ được tính và lưu tự động khi bấm Lưu.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fas fa-save me-1"></i> Lưu Hóa Đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Format tiền
function formatMoney(n) { return Number(n).toLocaleString('vi-VN'); }

// Cập nhật giá
function updateRow(selectElement) {
    const option = selectElement.options[selectElement.selectedIndex];
    const price = parseFloat(option.getAttribute('data-price')) || 0;
    const stock = parseInt(option.getAttribute('data-stock')) || 0;
    
    const row = selectElement.closest('tr');
    row.querySelector('.price-display').value = formatMoney(price);
    
    const qtyInput = row.querySelector('.qty');
    if(stock <= 0) {
        alert('Thuốc này hết hàng!');
        selectElement.value = "";
        row.querySelector('.price-display').value = 0;
        return;
    }
    qtyInput.max = stock;
    calcRow(qtyInput);
}

function calcRow(inputElement) {
    const row = inputElement.closest('tr');
    const select = row.querySelector('.med-select');
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.getAttribute('data-price')) || 0;
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    
    const stock = parseInt(option.getAttribute('data-stock')) || 0;
    if (qty > stock) {
        alert("Vượt quá tồn kho (" + stock + ")");
        row.querySelector('.qty').value = stock;
        return calcRow(row.querySelector('.qty'));
    }

    const total = price * qty;
    row.querySelector('.row-total').innerText = formatMoney(total);
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const txt = tr.querySelector('.row-total').innerText.replace(/\./g, '');
        total += parseFloat(txt) || 0;
    });
    document.getElementById('grandTotal').innerText = formatMoney(total);
}

document.getElementById('btnAddRow')?.addEventListener('click', function() {
    const tbody = document.getElementById('itemsBody');
    const first = tbody.querySelector('tr');
    const clone = first.cloneNode(true);
    
    clone.querySelector('select').value = "";
    clone.querySelector('.qty').value = 1;
    clone.querySelector('.price-display').value = 0;
    clone.querySelector('.row-total').innerText = "0";
    
    tbody.appendChild(clone);
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.btnDelRow')) {
        const tbody = document.getElementById('itemsBody');
        if (tbody.querySelectorAll('tr').length > 1) {
            e.target.closest('tr').remove();
            calcGrandTotal();
        } else { alert("Phải có ít nhất 1 dòng."); }
    }
});

// THỜI GIAN TĨNH KHI MỞ MODAL
const saleModal = document.getElementById('saleModal');
if (saleModal) {
    saleModal.addEventListener('show.bs.modal', function () {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const y = now.getFullYear();
        const H = String(now.getHours()).padStart(2, '0');
        const i = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('saleTime').value = `${d}/${m}/${y} ${H}:${i}:${s}`;
    });
}
</script>

<?php include __DIR__ . '/../Include/footer.php'; ?>