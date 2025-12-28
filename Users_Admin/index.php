<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) { 
    header("Location: ../Auth/login.php"); 
    exit(); 
}

include __DIR__ . '/../Include/header.php';
include __DIR__ . '/../Include/navbar.php';
include __DIR__ . '/../Include/menu.php';
?>

<style>
    /* 1. Nền trang web*/
    body {
        background-color: #fff5f5 !important;
        background-image: radial-gradient(#ffcdd2 1px, transparent 1px);
        background-size: 20px 20px;
    }

    /* 2. Banner chính */
    .hero-banner {
        background: linear-gradient(135deg, rgba(211, 47, 47, 0.8) 0%, rgba(255, 111, 0, 0.8) 100%),
                    url('../Asset/banner-tet.png'); 
        
        background-blend-mode: overlay;
        background-size: cover;
        background-position: center;
        
        border-radius: 20px; 
        padding: 40px; 
        color: white;
        position: relative; 
        overflow: hidden; 
        box-shadow: 0 10px 30px rgba(183, 28, 28, 0.3); 
        margin-bottom: 30px;
        border: 2px solid #ffeb3b;
    }
    
    .hero-title { font-weight: 800; font-size: 2.5rem; margin-bottom: 15px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }

    /* 3. Card Tin tức */
    .news-card { 
        background: #fff; 
        border-radius: 16px; 
        overflow: hidden; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
        transition: 0.3s; 
        height: 100%; 
        border: 1px solid #ffebee; 
    }
    
    .news-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 15px 30px rgba(211, 47, 47, 0.15); 
        border-color: #ffca28; 
    }
    
    .news-img { height: 180px; width: 100%; object-fit: cover; background-color: #ffebee; }
    .news-body { padding: 20px; position: relative; z-index: 2; }
    
    .text-tet-red { color: #d32f2f !important; }
    .news-tag { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #d32f2f; letter-spacing: 1px; }
</style>

<div class="container pb-5">
    
    <div class="hero-banner">
        <div class="row align-items-center">
            <div class="col-md-8 position-relative" style="z-index: 2;">
                <h1 class="hero-title">Chào Mừng Đến Với PharmaManager</h1>
                <p class="lead mb-4 opacity-90">Hệ thống quản lý nhà thuốc toàn diện, đạt chuẩn GPP. Giúp bạn theo dõi tồn kho, quản lý nhân viên và tối ưu hóa doanh thu.</p>
                <a href="medicines.php" class="btn btn-light text-danger fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fas fa-arrow-right me-2"></i> Truy Cập Kho Thuốc
                </a>
            </div>
        </div>
    </div>

    <h4 class="fw-bold mb-4 text-tet-red"><i class="fas fa-bullhorn me-2 text-warning"></i>Tin Tức & Chương Trình Ưu Đãi</h4>
    
    <div class="row g-4">
        <div class="col-md-4">
            <div class="news-card">
                <img src="../Asset/khuyen-mai-tet.png" class="news-img">
                <div class="news-body">
                    <div class="news-tag mb-2">Khuyến Mãi Tết</div>
                    <h5 class="fw-bold text-dark mb-2">Lì Xì Đầu Năm - Giảm Đến 20%</h5>
                    <p class="text-muted small mb-3">Áp dụng cho các dòng thực phẩm chức năng và quà biếu sức khỏe. Thời hạn đến hết mùng 10 Tết.</p>
                    <a href="#" class="text-danger fw-bold text-decoration-none small">Xem chi tiết <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="news-card">
                <img src="../Asset/hang-tet.jpg" class="news-img">
                <div class="news-body">
                    <div class="news-tag mb-2">Sản Phẩm Mới</div>
                    <h5 class="fw-bold text-dark mb-2">Hàng Tết Vừa Về Kho</h5>
                    <p class="text-muted small mb-3">Vừa nhập kho lô hàng trà thảo mộc và nước hồng sâm cao cấp, thích hợp làm quà tặng Tết.</p>
                    <a href="imports.php" class="text-danger fw-bold text-decoration-none small">Xem phiếu nhập <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="news-card">
                <img src="../Asset/lich.png" class="news-img">
                <div class="news-body">
                    <div class="news-tag mb-2 text-danger">Thông Báo</div>
                    <h5 class="fw-bold text-dark mb-2">Lịch Nghỉ Tết Nguyên Đán</h5>
                    <p class="text-muted small mb-3">Hệ thống sẽ bảo trì định kỳ vào ngày 30 Tết. Các nhà thuốc thành viên vui lòng chốt số liệu trước 17h.</p>
                    <a href="#" class="text-danger fw-bold text-decoration-none small">Chi tiết lịch nghỉ <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../Include/footer.php'; ?>