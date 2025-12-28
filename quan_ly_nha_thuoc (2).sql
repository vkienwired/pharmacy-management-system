-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th12 27, 2025 lúc 02:43 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quan_ly_nha_thuoc`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cong_ty`
--

CREATE TABLE `cong_ty` (
  `id_cong_ty` int(11) NOT NULL,
  `ten_cong_ty` varchar(150) NOT NULL,
  `loai_cong_ty` enum('NHA_CUNG_CAP','NHA_SAN_XUAT') NOT NULL DEFAULT 'NHA_CUNG_CAP',
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cong_ty`
--

INSERT INTO `cong_ty` (`id_cong_ty`, `ten_cong_ty`, `loai_cong_ty`, `so_dien_thoai`, `dia_chi`) VALUES
(1, 'Công ty Dược Hậu Giang', 'NHA_SAN_XUAT', NULL, NULL),
(2, 'Công ty CP Traphaco', 'NHA_SAN_XUAT', NULL, NULL),
(3, 'NCC Dược Phẩm TW1', 'NHA_CUNG_CAP', NULL, NULL),
(5, 'Công ty CP Dược phẩm Trung Ương 1 (Pharbaco)', 'NHA_CUNG_CAP', '02438454561', '160 Tôn Đức Thắng, Hà Nội'),
(6, 'Công ty CP Dược phẩm Imexpharm', 'NHA_CUNG_CAP', '02773862532', 'Số 4, Đ. 30/4, Cao Lãnh, Đồng Tháp'),
(7, 'Công ty TNHH MTV Dược Sài Gòn (Sapharco)', 'NHA_CUNG_CAP', '02838554577', '18-20 Nguyễn Trường Tộ, Quận 4, TP.HCM'),
(8, 'Công ty CP Dược - Trang thiết bị Y tế Bình Định (Bidiphar)', 'NHA_CUNG_CAP', '02563846500', '498 Nguyễn Thái Học, Quy Nhơn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_hoa_don`
--

CREATE TABLE `ct_hoa_don` (
  `id_ct_hoa_don` bigint(20) NOT NULL,
  `id_hoa_don` bigint(20) NOT NULL,
  `id_lo_thuoc` bigint(20) NOT NULL,
  `so_luong_ban` int(11) NOT NULL,
  `don_gia_ban` decimal(15,2) NOT NULL,
  `thanh_tien` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_hoa_don`
--

INSERT INTO `ct_hoa_don` (`id_ct_hoa_don`, `id_hoa_don`, `id_lo_thuoc`, `so_luong_ban`, `don_gia_ban`, `thanh_tien`) VALUES
(12, 12, 13, 4, 10000.00, 40000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_phieu_nhap`
--

CREATE TABLE `ct_phieu_nhap` (
  `id_ct_phieu_nhap` bigint(20) NOT NULL,
  `id_phieu_nhap` bigint(20) NOT NULL,
  `id_lo_thuoc` bigint(20) NOT NULL,
  `so_luong_nhap` int(11) NOT NULL,
  `don_gia_nhap` decimal(15,2) NOT NULL,
  `thanh_tien` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_phieu_nhap`
--

INSERT INTO `ct_phieu_nhap` (`id_ct_phieu_nhap`, `id_phieu_nhap`, `id_lo_thuoc`, `so_luong_nhap`, `don_gia_nhap`, `thanh_tien`) VALUES
(8, 10, 13, 4, 5000.00, 20000.00),
(9, 11, 14, 1, 17500.00, 17500.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `id_hoa_don` bigint(20) NOT NULL,
  `ma_hoa_don` varchar(50) NOT NULL,
  `thoi_gian` datetime DEFAULT current_timestamp(),
  `id_nhan_vien` bigint(20) NOT NULL,
  `tong_tien` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lo_thuoc`
--

CREATE TABLE `lo_thuoc` (
  `id_lo_thuoc` bigint(20) NOT NULL,
  `id_thuoc` bigint(20) NOT NULL,
  `so_lo` varchar(50) NOT NULL,
  `ngay_san_xuat` date DEFAULT NULL,
  `han_su_dung` date NOT NULL,
  `so_luong_ton` int(11) NOT NULL DEFAULT 0,
  `gia_nhap` decimal(15,2) DEFAULT 0.00,
  `id_cong_ty_cung_cap` int(11) DEFAULT NULL,
  `ngay_nhap` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lo_thuoc`
--

INSERT INTO `lo_thuoc` (`id_lo_thuoc`, `id_thuoc`, `so_lo`, `ngay_san_xuat`, `han_su_dung`, `so_luong_ton`, `gia_nhap`, `id_cong_ty_cung_cap`, `ngay_nhap`) VALUES
(13, 7, 'PN2025122620010793-1', NULL, '2099-12-31', 0, 5000.00, 3, '2025-12-27 02:01:07'),
(14, 12, 'PN2025122700135132-1', NULL, '2099-12-31', 1, 17500.00, 3, '2025-12-27 06:13:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id_nguoi_dung` bigint(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau_hash` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `id_vai_tro` int(11) DEFAULT NULL,
  `trang_thai` enum('HOAT_DONG','KHOA') NOT NULL DEFAULT 'HOAT_DONG',
  `tao_luc` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id_nguoi_dung`, `ho_ten`, `ten_dang_nhap`, `mat_khau_hash`, `so_dien_thoai`, `id_vai_tro`, `trang_thai`, `tao_luc`) VALUES
(9, 'Admin1', 'admin1', '$2y$10$VgVyPqnYqtUyq26wwYAC1uBHIxYf0AjneNKPV1xwZ5HTuyOy9wKp.', '0123456789', 1, 'HOAT_DONG', '2025-12-27 05:22:39'),
(10, 'Nhân viên 1', 'nhanvien1', '$2y$10$VEV51Q1bKqRboFNWKVs9S.W4HOSkwN7jvQbWfs2nVb1w6mPb314p6', '0987654321', 2, 'HOAT_DONG', '2025-12-27 05:23:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_nhap`
--

CREATE TABLE `phieu_nhap` (
  `id_phieu_nhap` bigint(20) NOT NULL,
  `ma_phieu_nhap` varchar(50) NOT NULL,
  `ngay_nhap` datetime DEFAULT current_timestamp(),
  `id_nhan_vien` bigint(20) NOT NULL,
  `id_cong_ty_cung_cap` int(11) DEFAULT NULL,
  `tong_tien` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_nhap`
--

INSERT INTO `phieu_nhap` (`id_phieu_nhap`, `ma_phieu_nhap`, `ngay_nhap`, `id_nhan_vien`, `id_cong_ty_cung_cap`, `tong_tien`, `ghi_chu`) VALUES
(11, 'PN2025122700135132', '2025-12-27 06:13:51', 9, 3, 17500.00, 'Nhập hàng đợt 1');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thuoc`
--

CREATE TABLE `thuoc` (
  `id_thuoc` bigint(20) NOT NULL,
  `ma_vach` varchar(50) NOT NULL,
  `ten_thuoc` varchar(200) NOT NULL,
  `dang_bao_che` varchar(100) DEFAULT NULL,
  `gia_ban_de_xuat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thuoc`
--

INSERT INTO `thuoc` (`id_thuoc`, `ma_vach`, `ten_thuoc`, `dang_bao_che`, `gia_ban_de_xuat`, `hinh_anh`, `mo_ta`) VALUES
(9, 'MV006', 'Decolgen Forte', 'Viên nén', 12000.00, 'Medicine_data/images02.jpg', NULL),
(10, 'MV007', 'Eugica Fort (Trị ho)', 'Viên nang mềm', 50000.00, 'Medicine_data/images05.jpg', NULL),
(12, 'MV009', 'Cao dán Salonpas (Hộp 20 miếng)', 'Hộp', 35000.00, 'Medicine_data/images01.jpg', NULL),
(14, 'MV011', 'Gaviscon Dual Action (Trị đau dạ dày)', 'Gói 10ml', 6500.00, 'Medicine_data/images15.jpg', NULL),
(16, 'MV013', 'Hoạt Huyết Nhất Nhất', 'Hộp 3 vỉ', 105000.00, 'Medicine_data/images225.jpg', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vai_tro`
--

CREATE TABLE `vai_tro` (
  `id_vai_tro` int(11) NOT NULL,
  `ten_vai_tro` varchar(50) NOT NULL,
  `mo_ta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vai_tro`
--

INSERT INTO `vai_tro` (`id_vai_tro`, `ten_vai_tro`, `mo_ta`) VALUES
(1, 'QUAN_TRI', 'Quản trị viên hệ thống'),
(2, 'DUOC_SI', 'Nhân viên bán hàng và kho');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `vw_doanh_thu_theo_ngay`
-- (See below for the actual view)
--
CREATE TABLE `vw_doanh_thu_theo_ngay` (
`ngay` date
,`so_hoa_don` bigint(21)
,`doanh_thu` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `vw_hoa_don_chi_tiet`
-- (See below for the actual view)
--
CREATE TABLE `vw_hoa_don_chi_tiet` (
`ma_hoa_don` varchar(50)
,`ten_thuoc` varchar(200)
,`so_luong_ban` int(11)
,`don_gia_ban` decimal(15,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `vw_ton_kho_theo_thuoc`
-- (See below for the actual view)
--
CREATE TABLE `vw_ton_kho_theo_thuoc` (
`id_thuoc` bigint(20)
,`ma_vach` varchar(50)
,`ten_thuoc` varchar(200)
,`don_vi_tinh` varchar(100)
,`tong_ton_kho` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `vw_doanh_thu_theo_ngay`
--
DROP TABLE IF EXISTS `vw_doanh_thu_theo_ngay`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_doanh_thu_theo_ngay`  AS SELECT cast(`hoa_don`.`thoi_gian` as date) AS `ngay`, count(`hoa_don`.`id_hoa_don`) AS `so_hoa_don`, sum(`hoa_don`.`tong_tien`) AS `doanh_thu` FROM `hoa_don` GROUP BY cast(`hoa_don`.`thoi_gian` as date) ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `vw_hoa_don_chi_tiet`
--
DROP TABLE IF EXISTS `vw_hoa_don_chi_tiet`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_hoa_don_chi_tiet`  AS SELECT `hd`.`ma_hoa_don` AS `ma_hoa_don`, `t`.`ten_thuoc` AS `ten_thuoc`, `ct`.`so_luong_ban` AS `so_luong_ban`, `ct`.`don_gia_ban` AS `don_gia_ban` FROM (((`ct_hoa_don` `ct` join `hoa_don` `hd` on(`ct`.`id_hoa_don` = `hd`.`id_hoa_don`)) join `lo_thuoc` `l` on(`ct`.`id_lo_thuoc` = `l`.`id_lo_thuoc`)) join `thuoc` `t` on(`l`.`id_thuoc` = `t`.`id_thuoc`)) ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `vw_ton_kho_theo_thuoc`
--
DROP TABLE IF EXISTS `vw_ton_kho_theo_thuoc`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ton_kho_theo_thuoc`  AS SELECT `t`.`id_thuoc` AS `id_thuoc`, `t`.`ma_vach` AS `ma_vach`, `t`.`ten_thuoc` AS `ten_thuoc`, `t`.`dang_bao_che` AS `don_vi_tinh`, coalesce(sum(`l`.`so_luong_ton`),0) AS `tong_ton_kho` FROM (`thuoc` `t` left join `lo_thuoc` `l` on(`t`.`id_thuoc` = `l`.`id_thuoc`)) GROUP BY `t`.`id_thuoc`, `t`.`ma_vach`, `t`.`ten_thuoc`, `t`.`dang_bao_che` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cong_ty`
--
ALTER TABLE `cong_ty`
  ADD PRIMARY KEY (`id_cong_ty`);

--
-- Chỉ mục cho bảng `ct_hoa_don`
--
ALTER TABLE `ct_hoa_don`
  ADD PRIMARY KEY (`id_ct_hoa_don`);

--
-- Chỉ mục cho bảng `ct_phieu_nhap`
--
ALTER TABLE `ct_phieu_nhap`
  ADD PRIMARY KEY (`id_ct_phieu_nhap`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`id_hoa_don`),
  ADD UNIQUE KEY `uq_mahd` (`ma_hoa_don`);

--
-- Chỉ mục cho bảng `lo_thuoc`
--
ALTER TABLE `lo_thuoc`
  ADD PRIMARY KEY (`id_lo_thuoc`),
  ADD KEY `idx_lo_thuoc_hsd` (`han_su_dung`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id_nguoi_dung`),
  ADD UNIQUE KEY `uq_username` (`ten_dang_nhap`),
  ADD UNIQUE KEY `uq_sdt` (`so_dien_thoai`);

--
-- Chỉ mục cho bảng `phieu_nhap`
--
ALTER TABLE `phieu_nhap`
  ADD PRIMARY KEY (`id_phieu_nhap`);

--
-- Chỉ mục cho bảng `thuoc`
--
ALTER TABLE `thuoc`
  ADD PRIMARY KEY (`id_thuoc`),
  ADD UNIQUE KEY `uq_mavach` (`ma_vach`);

--
-- Chỉ mục cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  ADD PRIMARY KEY (`id_vai_tro`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cong_ty`
--
ALTER TABLE `cong_ty`
  MODIFY `id_cong_ty` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `ct_hoa_don`
--
ALTER TABLE `ct_hoa_don`
  MODIFY `id_ct_hoa_don` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `ct_phieu_nhap`
--
ALTER TABLE `ct_phieu_nhap`
  MODIFY `id_ct_phieu_nhap` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `id_hoa_don` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `lo_thuoc`
--
ALTER TABLE `lo_thuoc`
  MODIFY `id_lo_thuoc` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id_nguoi_dung` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `phieu_nhap`
--
ALTER TABLE `phieu_nhap`
  MODIFY `id_phieu_nhap` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `thuoc`
--
ALTER TABLE `thuoc`
  MODIFY `id_thuoc` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  MODIFY `id_vai_tro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
