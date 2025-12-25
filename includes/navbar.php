<div class="header-wrapper">

    <div class="top-bar position-relative" style="z-index: 3;">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-3">
                <span><i class="fas fa-search-location me-1"></i> Hệ thống 150 nhà thuốc</span>
                <span class="d-none d-sm-block">|</span>
                <a href="#" class="d-none d-sm-block">Tải ứng dụng Pharma App</a>
            </div>
            <div>
                <a href="#"><i class="fas fa-headset me-1"></i> Tư vấn: 1800 6928</a>
            </div>
        </div>
    </div>

    <div class="main-header-content container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        
        <a href="index.php" class="header-logo">
            <i class="fas fa-prescription-bottle-alt"></i> PharmaManager
        </a>

        <div class="header-search shadow-sm">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Tìm tên thuốc, hoạt chất, thực phẩm chức năng...">
                <button class="btn" type="button"><i class="fas fa-search fa-lg"></i></button>
            </div>
        </div>

        <div class="dropdown">
            <div class="header-user d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <div class="text-end d-none d-lg-block">
                    <div style="font-size: 0.8rem; opacity: 0.9;">Xin chào,</div>
                    <div class="fw-bold"><?= $_SESSION['user_name'] ?? 'Dược sĩ' ?></div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'Admin') ?>&background=fff&color=d0021b" width="45" class="rounded-circle">
            </div>
            
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="border-radius: 12px;">
                <li><a class="dropdown-item py-2" href="#"><i class="fas fa-user me-2 text-muted"></i> Hồ sơ cá nhân</a></li>
                <li><a class="dropdown-item py-2" href="#"><i class="fas fa-bell me-2 text-muted"></i> Thông báo <span class="badge bg-danger rounded-pill ms-1">3</span></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger fw-bold py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</div>