<div class="container pb-5">
    <ul class="nav nav-pills mb-4 justify-content-center">
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>" href="index.php">
                <i class="fas fa-home me-2"></i>Trang Chủ
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='medicines.php'?'active':'' ?>" href="medicines.php">
                <i class="fas fa-cubes me-2"></i>Kho Thuốc
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='pos.php'?'active':'' ?>" href="pos.php">
                <i class="fas fa-history me-2"></i>Lịch Sử GD
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='imports.php'?'active':'' ?>" href="imports.php">
                <i class="fas fa-truck-loading me-2"></i>Nhập Hàng
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='staff.php'?'active':'' ?>" href="staff.php">
                <i class="fas fa-users me-2"></i>Nhân Viên
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='reports.php'?'active':'' ?>" href="reports.php">
                <i class="fas fa-chart-line me-2"></i>Báo Cáo
            </a>
        </li>
    </ul>