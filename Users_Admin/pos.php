<?php


session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header("Location: ../Auth/login.php");
    exit();
}
require_once __DIR__ . "/../Include/db.php";

include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';

// Hàm format thời gian kiểu 

function formatTimeSmart($datetimeStr)
{
    $ts = strtotime($datetimeStr);
    $today = date("Y-m-d");
    $thatDay = date("Y-m-d", $ts);

    if ($today === $thatDay) return date("H:i", $ts) . " Hôm nay";
    return date("d/m/Y H:i", $ts);
}

// 1) Nhận filter từ URL (GET)
$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');
$staff_id = trim($_GET['staff_id'] ?? '');
$company_id = trim($_GET['company_id'] ?? '');

if ($from === '' || $to === '') {
    $from = date('Y-m-d', strtotime('-7 days'));
    $to   = date('Y-m-d');
}

$fromDT = $from . " 00:00:00";
$toDT   = $to   . " 23:59:59";

// 2) Dropdown Nhân viên

$staffs = [];
try {
    $stmtStaff = $pdo->query("SELECT id_nguoi_dung, ho_ten FROM nguoi_dung WHERE trang_thai = 'HOAT_DONG' ORDER BY ho_ten");
    $staffs = $stmtStaff->fetchAll();
} catch (PDOException $e) { $staffs = []; }

// 3) Dropdown Công ty (NCC)
$companies = [];
try {
    $stmtC = $pdo->query("SELECT id_cong_ty, ten_cong_ty FROM cong_ty WHERE loai_cong_ty = 'NHA_CUNG_CAP' ORDER BY ten_cong_ty");
    $companies = $stmtC->fetchAll();
} catch (PDOException $e) { $companies = []; }


// 4) Query giao dịch: UNION BÁN + NHẬP có filter

$params = [':from' => $fromDT, ':to' => $toDT];
$whereSale = "WHERE hd.thoi_gian BETWEEN :from AND :to";
$whereImport = "WHERE pn.ngay_nhap BETWEEN :from AND :to";

if ($staff_id !== '') {
    $whereSale   .= " AND hd.id_nhan_vien = :staff_id";
    $whereImport .= " AND pn.id_nhan_vien = :staff_id";
    $params[':staff_id'] = $staff_id;
}

if ($company_id !== '') {
    $whereImport .= " AND pn.id_cong_ty_cung_cap = :company_id";
    $params[':company_id'] = $company_id;
}

$rows = [];
$error_query = "";

try {
    $sql = "
        (
            SELECT 'BAN' AS loai_gd, hd.ma_hoa_don AS ma_gd, hd.thoi_gian AS thoi_gian, hd.tong_tien AS tong_tien, 
                   nd.ho_ten AS ten_nhan_vien, NULL AS ten_cong_ty
            FROM hoa_don hd
            JOIN nguoi_dung nd ON nd.id_nguoi_dung = hd.id_nhan_vien
            $whereSale
        )
        UNION ALL
        (
            SELECT 'NHAP' AS loai_gd, pn.ma_phieu_nhap AS ma_gd, pn.ngay_nhap AS thoi_gian, pn.tong_tien AS tong_tien,
                   nd.ho_ten AS ten_nhan_vien, ct.ten_cong_ty AS ten_cong_ty
            FROM phieu_nhap pn
            LEFT JOIN nguoi_dung nd ON nd.id_nguoi_dung = pn.id_nhan_vien
            LEFT JOIN cong_ty ct ON ct.id_cong_ty = pn.id_cong_ty_cung_cap
            $whereImport
        )
        ORDER BY thoi_gian DESC LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_query = $e->getMessage();
    $rows = [];
}

// 5) Prepare chi tiết

try {
    $stmtSaleDetail = $pdo->prepare("SELECT ten_thuoc, so_luong_ban FROM vw_hoa_don_chi_tiet WHERE ma_hoa_don = ? ORDER BY ten_thuoc LIMIT 30");
    $stmtImportDetail = $pdo->prepare("
        SELECT t.ten_thuoc, ctpn.so_luong_nhap 
        FROM phieu_nhap pn 
        JOIN ct_phieu_nhap ctpn ON ctpn.id_phieu_nhap = pn.id_phieu_nhap 
        JOIN lo_thuoc lt ON lt.id_lo_thuoc = ctpn.id_lo_thuoc 
        JOIN thuoc t ON t.id_thuoc = lt.id_thuoc 
        WHERE pn.ma_phieu_nhap = ? 
        ORDER BY t.ten_thuoc LIMIT 30
    ");
} catch (PDOException $e) { die("Lỗi prepare chi tiết: " . $e->getMessage()); }
?>

<h4 class="fw-bold mb-3">Lịch Sử Giao Dịch</h4>

<div class="card-custom mb-3">
    <div class="p-3 border-bottom bg-white">
        <form class="row g-2 align-items-end" method="GET" action="">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Từ ngày</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Đến ngày</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Nhân viên</label>
                <select class="form-select form-select-sm" name="staff_id">
                    <option value="">Tất cả</option>
                    <?php foreach ($staffs as $s): ?>
                        <option value="<?= htmlspecialchars($s['id_nguoi_dung']) ?>" <?= ($staff_id == $s['id_nguoi_dung']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['ho_ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Công ty (NCC)</label>
                <select class="form-select form-select-sm" name="company_id">
                    <option value="">Tất cả</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= htmlspecialchars($c['id_cong_ty']) ?>" <?= ($company_id == $c['id_cong_ty']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['ten_cong_ty']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Lọc</button>
                <a class="btn btn-sm btn-outline-secondary" href="pos.php"><i class="fas fa-rotate-left me-1"></i> Reset</a>
            </div>
            <div class="col-12 small text-muted mt-1">
                Đang lọc: <b><?= htmlspecialchars($from) ?></b> → <b><?= htmlspecialchars($to) ?></b>
                <?php if ($staff_id !== ''): ?> | Nhân viên đã chọn <?php endif; ?>
                <?php if ($company_id !== ''): ?> | NCC đã chọn <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card-custom p-0">
    <table class="table table-hover mb-0 align-middle">
        <thead class="bg-light">
            <tr>
                <th>Mã GD</th><th>Thời Gian</th><th>Nhân Viên</th><th>Công ty</th><th>Chi Tiết</th><th>Tổng Tiền</th><th>Loại</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($error_query): ?>
            <tr><td colspan="7" class="p-4"><div class="alert alert-danger m-0"><b>Lỗi query:</b><br><?= htmlspecialchars($error_query) ?></div></td></tr>
        <?php elseif (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted p-4">Không có giao dịch trong khoảng ngày đã chọn.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <?php
                    $loai = $r['loai_gd'] ?? '';
                    $maGD = htmlspecialchars($r['ma_gd'] ?? '');
                    $timeText = formatTimeSmart($r['thoi_gian'] ?? '');
                    $nhanVien = htmlspecialchars($r['ten_nhan_vien'] ?? '—');
                    $congTy = htmlspecialchars($r['ten_cong_ty'] ?? '—');
                    $tongTien = number_format((float)($r['tong_tien'] ?? 0));
                    $detailParts = [];

                    if ($loai === 'BAN') {
                        $stmtSaleDetail->execute([$r['ma_gd']]);
                        $items = $stmtSaleDetail->fetchAll();
                        foreach ($items as $it) $detailParts[] = ($it['ten_thuoc']??'') . " (" . (int)($it['so_luong_ban']??0) . ")";
                    } else {
                        $stmtImportDetail->execute([$r['ma_gd']]);
                        $items = $stmtImportDetail->fetchAll();
                        foreach ($items as $it) $detailParts[] = ($it['ten_thuoc']??'') . " (" . (int)($it['so_luong_nhap']??0) . ")";
                    }
                    $chiTiet = !empty($detailParts) ? htmlspecialchars(implode(", ", $detailParts)) : "—";
                ?>
                <tr>
                    <td class="fw-bold text-primary">#<?= $maGD ?></td>
                    <td><?= htmlspecialchars($timeText) ?></td>
                    <td><?= $nhanVien ?></td>
                    <td><?= ($loai === 'NHAP') ? $congTy : "—" ?></td>
                    <td><?= $chiTiet ?></td>
                    <td class="fw-bold"><?= $tongTien ?> đ</td>
                    <td>
                        <?php if ($loai === 'BAN'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success">Bán hàng</span>
                        <?php else: ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary">Nhập kho</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../Include/footer.php'; ?>