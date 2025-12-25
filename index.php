<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) { header("Location: login.php"); exit(); }

// Gọi các khối giao diện đã tách
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/menu.php';
?>

<div class="container">
    
    <div class="hero-banner">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="hero-title">Chào Mừng Đến Với PharmaManager</h1>
                <p class="lead mb-4 opacity-75">Hệ thống quản lý nhà thuốc toàn diện, đạt chuẩn GPP. Giúp bạn theo dõi tồn kho, quản lý nhân viên và tối ưu hóa doanh thu.</p>
                <a href="medicines.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm"><i class="fas fa-arrow-right me-2"></i> Truy Cập Kho Thuốc</a>
            </div>
            <div class="col-md-4 d-none d-md-block">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/pharmacist-illustration-download-in-svg-png-gif-file-formats--drugstore-pharmacy-chemist-shop-medical-pack-healthcare-illustrations-4384296.png?f=webp" class="hero-img">
            </div>
        </div>
    </div>

    <h4 class="fw-bold mb-4"><i class="fas fa-bullhorn me-2 text-warning"></i>Tin Tức & Chương Trình Ưu Đãi</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="news-card">
                <img src="https://nhathuoclongchau.com.vn/images/khuyen-mai/khuyen-mai-thang-12-2023.jpg" class="news-img" style="background-color: #ffeaa7;">
                <div class="news-body">
                    <div class="news-tag mb-2">Khuyến Mãi</div>
                    <h5 class="fw-bold text-dark mb-2">Siêu Sale Cuối Tháng - Giảm Đến 20%</h5>
                    <p class="text-muted small mb-3">Áp dụng cho các dòng thực phẩm chức năng và vitamin tổng hợp. Thời hạn đến hết 30/12.</p>
                    <a href="#" class="text-primary fw-bold text-decoration-none small">Xem chi tiết <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="news-card">
                <img src="https://cdn.nhathuoclongchau.com.vn/unsafe/800x0/filters:quality(90)/https://cms-prod.s3-sgn09.fptcloud.com/DSCF_8716_c6c4078878.jpg" class="news-img">
                <div class="news-body">
                    <div class="news-tag mb-2">Sản Phẩm Mới</div>
                    <h5 class="fw-bold text-dark mb-2">Nhập Kho: Khẩu Trang 4D Cao Cấp</h5>
                    <p class="text-muted small mb-3">Vừa nhập kho lô hàng khẩu trang y tế kháng khuẩn 4 lớp, đạt chuẩn xuất khẩu Châu Âu.</p>
                    <a href="imports.php" class="text-primary fw-bold text-decoration-none small">Xem phiếu nhập <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="news-card">
                <div class="news-img d-flex align-items-center justify-content-center bg-info bg-opacity-10">
                    <i class="fas fa-user-md fa-4x text-info"></i>
                </div>
                <div class="news-body">
                    <div class="news-tag mb-2 text-info">Thông Báo</div>
                    <h5 class="fw-bold text-dark mb-2">Lịch Đào Tạo Dược Sĩ Tháng Tới</h5>
                    <p class="text-muted small mb-3">Cập nhật kiến thức về các loại thuốc kháng sinh mới và quy trình tư vấn khách hàng chuẩn GPP.</p>
                    <a href="#" class="text-primary fw-bold text-decoration-none small">Đăng ký ngay <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>