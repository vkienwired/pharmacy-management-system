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

// Lấy doanh thu theo ngày (30 ngày gần nhất)

try {
    $stmt = $pdo->prepare("
        SELECT ngay, so_hoa_don, doanh_thu
        FROM vw_doanh_thu_theo_ngay
        WHERE ngay >= (CURDATE() - INTERVAL 30 DAY)
        ORDER BY ngay DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi query vw_doanh_thu_theo_ngay: " . $e->getMessage());
}


// Tính tổng (30 ngày)

$tongDoanhThu30 = 0;
$tongHoaDon30 = 0;

foreach ($rows as $r) {
    $tongDoanhThu30 += (float)($r['doanh_thu'] ?? 0);
    $tongHoaDon30 += (int)($r['so_hoa_don'] ?? 0);
}


//Lấy số hóa đơn hôm nay + doanh thu hôm nay

$doanhThuHomNay = 0;
$hoaDonHomNay = 0;

foreach ($rows as $r) {
    if (($r['ngay'] ?? '') === date('Y-m-d')) {
        $doanhThuHomNay = (float)($r['doanh_thu'] ?? 0);
        $hoaDonHomNay = (int)($r['so_hoa_don'] ?? 0);
        break;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold m-0">Báo Cáo Doanh Thu</h4>
    <span class="text-muted small">
        Cập nhật từ dữ liệu bán hàng trong DB
    </span>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Doanh thu 30 ngày</div>
                    <div class="fw-bold fs-4"><?= number_format($tongDoanhThu30) ?> đ</div>
                </div>
                <div class="fs-3 text-success">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="text-muted small mt-2">Tính từ VIEW <b>vw_doanh_thu_theo_ngay</b></div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Số hóa đơn 30 ngày</div>
                    <div class="fw-bold fs-4"><?= number_format($tongHoaDon30) ?></div>
                </div>
                <div class="fs-3 text-primary">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
            <div class="text-muted small mt-2">Tổng số hóa đơn bán ra</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Hôm nay</div>
                    <div class="fw-bold fs-5"><?= number_format($doanhThuHomNay) ?> đ</div>
                    <div class="text-muted small"><?= number_format($hoaDonHomNay) ?> hóa đơn</div>
                </div>
                <div class="fs-3 text-danger">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
            <div class="text-muted small mt-2">Theo ngày hiện tại</div>
        </div>
    </div>
</div>

<div class="card-custom p-0">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold m-0">
            <i class="fas fa-table me-2 text-primary"></i>Doanh thu theo ngày (30 ngày)
        </h5>
        <span class="text-muted small">Sắp xếp: mới → cũ</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th>Ngày</th>
                    <th>Số hóa đơn</th>
                    <th>Doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted p-4">
                            Chưa có dữ liệu doanh thu trong 30 ngày gần nhất.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $ngay = $r['ngay'] ?? '';
                            $soHD = (int)($r['so_hoa_don'] ?? 0);
                            $doanhThu = (float)($r['doanh_thu'] ?? 0);
                            $ngayHienThi = $ngay ? date("d/m/Y", strtotime($ngay)) : "—";
                            $isToday = ($ngay === date('Y-m-d'));
                        ?>
                        <tr class="<?= $isToday ? 'table-success' : '' ?>">
                            <td class="fw-bold"><?= htmlspecialchars($ngayHienThi) ?></td>
                            <td><?= number_format($soHD) ?></td>
                            <td class="fw-bold text-primary"><?= number_format($doanhThu) ?> đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 

include __DIR__ . '/../Include/footer.php'; 
?>