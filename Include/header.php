<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaManager - Quản Lý Nhà Thuốc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #00b09b; 
            --tet-red: #d0021b; 
            --tet-yellow: #fdd835; 
            --light: #f4f6f9; 
        }
        body { font-family: 'Poppins', sans-serif; background: var(--light); color: #2d3436; font-size: 0.95rem; }
        
        .header-wrapper {
            /* 1. Đường dẫn ảnh */
            background-image: url('../Asset/banner-tet.png'); 
            
            /* 2. Điều chỉnh độ cao Header */
            /* 120px - 150px là kích thước chuẩn đẹp cho Header */
            min-height: 130px; 
            
            /* 3. Chỉnh cách hiển thị ảnh nền */
            /* 'cover': Ảnh tự cắt bớt để lấp đầy khung (Đẹp nhất, không méo) */
            /* '100% 100%': Ảnh bị ép co giãn cho vừa khung (Sẽ bị méo hình) */
            background-size: cover; 
            
            background-position: center;
            background-repeat: no-repeat;
            
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Thêm bóng đổ cho chữ để dễ đọc trên nền ảnh rực rỡ */
        .header-logo, .top-bar a, .header-user {
            text-shadow: 0 2px 4px rgba(0,0,0,0.6); 
        }

        /* Thanh thông tin nhỏ trên cùng */
        .top-bar {
            background: rgba(0, 0, 0, 0.1);
            padding: 5px 0;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .top-bar a { color: #fff; text-decoration: none; margin-left: 15px; }
        .top-bar a:hover { text-decoration: underline; }

        /* Phần Header chính */
        .main-header-content {
            padding: 15px 0 20px 0;
            position: relative;
            z-index: 2; /* Để nội dung nổi lên trên hình nền */
        }

        /* Logo */
        .header-logo {
            font-size: 1.8rem; font-weight: 800; color: #fff !important; text-decoration: none;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex; align-items: center; gap: 10px;
        }

        /* Thanh tìm kiếm ở giữa */
        .header-search {
            max-width: 600px;
            width: 100%;
        }
        .header-search input {
            border-radius: 30px 0 0 30px;
            border: none;
            padding: 12px 20px;
            font-size: 0.95rem;
        }
        .header-search button {
            border-radius: 0 30px 30px 0;
            background: #fff; border: none; color: var(--tet-red);
            padding-right: 20px;
        }
        .header-search button:hover { color: #b71c1c; }

        /* User Info bên phải */
        .header-user { color: #fff; font-weight: 600; cursor: pointer; }
        .header-user img { border: 2px solid #fff; }

        /* Nav Pills (Menu dưới) */
        .nav-pills .nav-link { 
            border-radius: 30px; padding: 10px 25px; font-weight: 600; 
            color: #636e72; background: #fff; margin-right: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.3s;
        }
        .nav-pills .nav-link:hover { transform: translateY(-2px); color: var(--tet-red); }
        .nav-pills .nav-link.active { background: var(--tet-red); color: #fff; }
        .main-footer { 
            background-color: #2c3e50; /* Nền xanh đen */
            color: #bdc3c7; /* Chữ xám nhạt */
            padding-top: 60px; 
            margin-top: 60px; 
            border-top: 5px solid var(--primary);
        }
        
        .footer-title { 
            font-weight: 700; 
            font-size: 1.1rem; 
            margin-bottom: 20px; 
            color: #fff; /* Tiêu đề trắng */
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .footer-link { 
            display: block; 
            color: #bdc3c7; 
            text-decoration: none; 
            margin-bottom: 12px; 
            font-size: 0.95rem; 
            transition: 0.3s;
        }
        
        .footer-link:hover { 
            color: #fff; 
            padding-left: 8px; /* Hiệu ứng đẩy sang phải */
        }
        
        .footer-contact strong { color: #fff; } 
        
        .copyright { 
            background: #1a252f; /* Nền đen hơn cho dòng bản quyền */
            padding: 20px 0; 
            margin-top: 40px; 
            font-size: 0.85rem; 
            color: #7f8c8d;
            border-top: 1px solid #34495e; 
        } 
        /* Card chung */
        .card-custom { background: #fff; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); border: none; overflow: hidden; }
        .med-thumb { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid #eee; }
    </style>
</head>
<body>