

DROP DATABASE IF EXISTS `quan_ly_nha_thuoc`;
CREATE DATABASE `quan_ly_nha_thuoc` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quan_ly_nha_thuoc`;

-- ----------------------------------------------------------
-- 1. Bảng Vai trò (Phân quyền)
-- ----------------------------------------------------------
CREATE TABLE `vai_tro` (
  `id_vai_tro` int(11) NOT NULL AUTO_INCREMENT,
  `ten_vai_tro` varchar(50) NOT NULL, -- VD: QUAN_TRI, DUOC_SI
  `mo_ta` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_vai_tro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vai_tro` (`ten_vai_tro`, `mo_ta`) VALUES
('QUAN_TRI', 'Quản trị viên hệ thống'),
('DUOC_SI', 'Nhân viên bán hàng và kho');

-- ----------------------------------------------------------
-- 2. Bảng Người dùng (Nhân viên)
-- ----------------------------------------------------------
CREATE TABLE `nguoi_dung` (
  `id_nguoi_dung` bigint(20) NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau_hash` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `id_vai_tro` int(11) DEFAULT NULL,
  `trang_thai` enum('HOAT_DONG','KHOA') NOT NULL DEFAULT 'HOAT_DONG',
  `tao_luc` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_nguoi_dung`),
  UNIQUE KEY `uq_username` (`ten_dang_nhap`),
  UNIQUE KEY `uq_sdt` (`so_dien_thoai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mật khẩu mẫu là: 123456 (đã hash)
INSERT INTO `nguoi_dung` (`ho_ten`, `ten_dang_nhap`, `mat_khau_hash`, `so_dien_thoai`, `id_vai_tro`) VALUES 
('Admin Quản Trị', 'admin', '$2y$10$C7.7yZ3d4/..sampleHashFor123456...', '0909123456', 1),
('Dược sĩ A', 'user01', '$2y$10$wK8r.M2Z9.123456hashPlaceholder...', '0912345678', 2);
-- Lưu ý: Bạn nên dùng file set_password.php để reset lại pass chuẩn cho user01 nếu cần.

-- ----------------------------------------------------------
-- 3. Bảng Công ty (Nhà cung cấp / Nhà sản xuất)
-- Cần thiết cho chức năng lọc ở pos.php và nhập hàng
-- ----------------------------------------------------------
CREATE TABLE `cong_ty` (
  `id_cong_ty` int(11) NOT NULL AUTO_INCREMENT,
  `ten_cong_ty` varchar(150) NOT NULL,
  `loai_cong_ty` enum('NHA_CUNG_CAP','NHA_SAN_XUAT') NOT NULL DEFAULT 'NHA_CUNG_CAP',
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_cong_ty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cong_ty` (`ten_cong_ty`, `loai_cong_ty`) VALUES 
('Công ty Dược Hậu Giang', 'NHA_SAN_XUAT'),
('Công ty CP Traphaco', 'NHA_SAN_XUAT'),
('NCC Dược Phẩm TW1', 'NHA_CUNG_CAP'),
('NCC Dược Sài Gòn', 'NHA_CUNG_CAP');

-- ----------------------------------------------------------
-- 4. Bảng Thuốc (Danh mục sản phẩm)
-- ----------------------------------------------------------
CREATE TABLE `thuoc` (
  `id_thuoc` bigint(20) NOT NULL AUTO_INCREMENT,
  `ma_vach` varchar(50) NOT NULL,
  `ten_thuoc` varchar(200) NOT NULL,
  `dang_bao_che` varchar(100) DEFAULT NULL, -- Viên nén, Sirô...
  `gia_ban_de_xuat` decimal(15,2) NOT NULL DEFAULT 0,
  `hinh_anh` varchar(255) DEFAULT NULL, -- Lưu đường dẫn tương đối: Medicine_data/abc.jpg
  `mo_ta` text DEFAULT NULL,
  PRIMARY KEY (`id_thuoc`),
  UNIQUE KEY `uq_mavach` (`ma_vach`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu thuốc
INSERT INTO `thuoc` (`ma_vach`, `ten_thuoc`, `dang_bao_che`, `gia_ban_de_xuat`) VALUES 
('MV001', 'Panadol Extra', 'Viên nén', 15000),
('MV002', 'Berberin', 'Viên nén', 5000),
('MV003', 'Vitamin C 500mg', 'Viên sủi', 25000),
('MV004', 'Siro Prospan', 'Chai 100ml', 85000);

-- ----------------------------------------------------------
-- 5. Bảng Lô thuốc (Quản lý tồn kho & Hạn dùng)
-- Quan trọng cho logic FIFO của sales.php
-- ----------------------------------------------------------
CREATE TABLE `lo_thuoc` (
  `id_lo_thuoc` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_thuoc` bigint(20) NOT NULL,
  `so_lo` varchar(50) NOT NULL,
  `ngay_san_xuat` date DEFAULT NULL,
  `han_su_dung` date NOT NULL,
  `so_luong_ton` int(11) NOT NULL DEFAULT 0,
  `gia_nhap` decimal(15,2) DEFAULT 0,
  `id_cong_ty_cung_cap` int(11) DEFAULT NULL, -- Link tới bảng cong_ty
  `ngay_nhap` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_lo_thuoc`),
  KEY `idx_lo_thuoc_hsd` (`han_su_dung`) -- Index để sort FIFO nhanh
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu lô thuốc (Tồn kho)
INSERT INTO `lo_thuoc` (`id_thuoc`, `so_lo`, `han_su_dung`, `so_luong_ton`, `gia_nhap`, `id_cong_ty_cung_cap`) VALUES 
(1, 'L001', '2026-12-31', 100, 12000, 3), -- Panadol
(1, 'L002', '2025-06-01', 50, 11500, 3),  -- Panadol (Hết hạn sớm hơn -> sẽ bán trước)
(2, 'L003', '2027-01-01', 200, 3000, 4),  -- Berberin
(3, 'L004', '2025-10-20', 80, 20000, 3),  -- Vitamin C
(4, 'L005', '2026-05-15', 30, 70000, 4);  -- Prospan

-- ----------------------------------------------------------
-- 6. Bảng Hóa đơn (Bán hàng - Header)
-- ----------------------------------------------------------
CREATE TABLE `hoa_don` (
  `id_hoa_don` bigint(20) NOT NULL AUTO_INCREMENT,
  `ma_hoa_don` varchar(50) NOT NULL, -- HDyyyymmdd...
  `thoi_gian` datetime DEFAULT current_timestamp(),
  `id_nhan_vien` bigint(20) NOT NULL,
  `tong_tien` decimal(15,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_hoa_don`),
  UNIQUE KEY `uq_mahd` (`ma_hoa_don`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Bảng Chi tiết hóa đơn (Bán hàng - Detail)
-- Liên kết Lô thuốc để biết bán từ lô nào
-- ----------------------------------------------------------
CREATE TABLE `ct_hoa_don` (
  `id_ct_hoa_don` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_hoa_don` bigint(20) NOT NULL,
  `id_lo_thuoc` bigint(20) NOT NULL,
  `so_luong_ban` int(11) NOT NULL,
  `don_gia_ban` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id_ct_hoa_don`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. Bảng Phiếu nhập (Nhập hàng - Header)
-- Dùng cho pos.php (lịch sử nhập)
-- ----------------------------------------------------------
CREATE TABLE `phieu_nhap` (
  `id_phieu_nhap` bigint(20) NOT NULL AUTO_INCREMENT,
  `ma_phieu_nhap` varchar(50) NOT NULL,
  `ngay_nhap` datetime DEFAULT current_timestamp(),
  `id_nhan_vien` bigint(20) NOT NULL,
  `id_cong_ty_cung_cap` int(11) DEFAULT NULL,
  `tong_tien` decimal(15,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_phieu_nhap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Bảng Chi tiết phiếu nhập
-- ----------------------------------------------------------
CREATE TABLE `ct_phieu_nhap` (
  `id_ct_phieu_nhap` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_phieu_nhap` bigint(20) NOT NULL,
  `id_lo_thuoc` bigint(20) NOT NULL,
  `so_luong_nhap` int(11) NOT NULL,
  `don_gia_nhap` decimal(15,2) NOT NULL,
  `thanh_tien` decimal(15,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_ct_phieu_nhap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- CÁC VIEW (QUAN TRỌNG CHO FILE PHP)
-- ==========================================================

-- VIEW 1: Tính tổng tồn kho theo thuốc (Gộp các lô lại)
-- Dùng cho: medicines.php, sales.php
CREATE OR REPLACE VIEW `vw_ton_kho_theo_thuoc` AS
SELECT 
    t.id_thuoc,
    t.ma_vach,
    t.ten_thuoc,
    t.dang_bao_che as don_vi_tinh, -- Map cột này để khớp code cũ nếu cần
    COALESCE(SUM(l.so_luong_ton), 0) AS tong_ton_kho
FROM `thuoc` t
LEFT JOIN `lo_thuoc` l ON t.id_thuoc = l.id_thuoc
GROUP BY t.id_thuoc, t.ma_vach, t.ten_thuoc, t.dang_bao_che;

-- VIEW 2: Thống kê doanh thu theo ngày
-- Dùng cho: reports.php
CREATE OR REPLACE VIEW `vw_doanh_thu_theo_ngay` AS
SELECT
    DATE(thoi_gian) AS ngay,
    COUNT(id_hoa_don) AS so_hoa_don,
    SUM(tong_tien) AS doanh_thu
FROM `hoa_don`
GROUP BY DATE(thoi_gian);

-- VIEW 3: Chi tiết hóa đơn (kèm tên thuốc)
-- Dùng cho: pos.php (hiển thị chi tiết giao dịch)
CREATE OR REPLACE VIEW `vw_hoa_don_chi_tiet` AS
SELECT 
    hd.ma_hoa_don,
    t.ten_thuoc,
    ct.so_luong_ban,
    ct.don_gia_ban
FROM `ct_hoa_don` ct
JOIN `hoa_don` hd ON ct.id_hoa_don = hd.id_hoa_don
JOIN `lo_thuoc` l ON ct.id_lo_thuoc = l.id_lo_thuoc
JOIN `thuoc` t ON l.id_thuoc = t.id_thuoc;
-- 1. Xóa user cũ (nếu có) để tránh trùng lặp
DELETE FROM `nguoi_dung` WHERE `ten_dang_nhap` = 'admin';

-- 2. Tạo tài khoản Admin mới
-- Lưu ý: Ở đây ta lưu mật khẩu dạng thường '123456'
-- File login.php của bạn có chế độ fallback nên sẽ đăng nhập được.
INSERT INTO `nguoi_dung` 
(`ho_ten`, `ten_dang_nhap`, `mat_khau_hash`, `so_dien_thoai`, `id_vai_tro`, `trang_thai`) 
VALUES 
('Admin Cứu Hộ', 'admin', '123456', '0900000000', 1, 'HOAT_DONG');