# Monochrome News Flash - Laravel Backend API

Backend API cho ứng dụng tin tức Monochrome News Flash, được xây dựng với Laravel 11 và MySQL.

## Tính năng

- RESTful API cho quản lý tin tức
- Quản lý danh mục tin tức
- Quản lý người dùng và phân quyền
- Hệ thống permissions dựa trên roles
- CORS được cấu hình sẵn cho ReactJS/Next.js
- Dữ liệu mẫu hoàn chỉnh (Seeded mock data)

## Yêu cầu hệ thống (Prerequisites)

- PHP >= 8.1
- Composer
- MySQL
- Laragon / XAMPP / MAMP (Recommended cho thiết lập local)

---

## 🚀 Cài đặt tự động (Automated Setup)

Để giúp quá trình cài đặt nhanh chóng và dễ dàng nhất, dự án cung cấp các script cài đặt tự động cho cả Windows và Linux/macOS. Các script này sẽ tự động copy file `.env`, cài đặt các dependencies của Composer, và generate Application Key.

### 🪟 Dành cho Windows (PowerShell)

1. Mở PowerShell (hoặc Terminal trong VS Code).
2. Trỏ đường dẫn vào thư mục `backend`.
3. Chạy script cài đặt:
   ```powershell
   .\setup.ps1
   ```
*(Lưu ý: Nếu bạn gặp lỗi Execution Policy, hãy chạy lệnh `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass` trước khi chạy script).*

### 🐧 Dành cho Linux / macOS (Bash)

1. Mở Terminal.
2. Trỏ đường dẫn tới thư mục `backend`.
3. Cấp quyền thực thi và chạy script:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

---

## ⚙️ Cấu hình Database (Database Configuration)

Sau khi chạy script tự động, bạn cần cấu hình database và chạy migrations để khởi tạo dữ liệu.

### 1. Cập nhật `.env`
Mở file `.env` ở thư mục gốc của backend và điền thông tin MySQL của bạn:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monochrome_news
DB_USERNAME=root
DB_PASSWORD=          # Để trống nếu bạn dùng XAMPP/Laragon mặc định
```

### 2. Tạo Database trống
Truy cập MySQL (qua HeidiSQL, phpMyAdmin, hoặc CLI) và chạy lệnh tạo database:
```sql
CREATE DATABASE monochrome_news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Migrates và Seeders
Chạy lệnh sau để tạo các bảng và chèn dữ liệu mẫu (Tin tức, User, Danh mục):
```bash
php artisan migrate:fresh --seed
```

---

## 🏃 Khởi động Server

Khởi động Laravel Server cho môi trường phát triển:

```bash
php artisan serve
```

API sẽ chạy tại: `http://localhost:8000`

### Kiểm tra kết nối (Verification)

Mở browser hoặc Postman và truy cập:
```
http://localhost:8000/api/health
```

Kết quả mong đợi:
```json
{
  "status": "ok",
  "message": "API is running",
  "timestamp": "2025-11-06T10:30:00.000000Z"
}
```

---

## 📚 Tài liệu bổ sung (Documentation Index)

Hệ thống có các tài liệu chi tiết cho từng cấu phần khác nhau. Vui lòng tham khảo:

- [`DATABASE_STRUCTURE.md`](DATABASE_STRUCTURE.md) - Cấu trúc chi tiết của Database
- [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) - Danh sách các API Endpoints
- [`SECURITY_GUIDE.md`](SECURITY_GUIDE.md) - Hướng dẫn Bảo mật và Phân quyền
- [`FRONTEND_INTEGRATION.md`](FRONTEND_INTEGRATION.md) - Hướng dẫn tích hợp React/Next.js
- [`JWT_SETUP.md`](JWT_SETUP.md) - Thiết lập Authentication bằng JWT

##  Các Lệnh Hữu ích (Useful Commands)

```bash
# Xem danh sách các endpoint (Routes) hiện có
php artisan route:list

# Xóa cache (rất cần thiết khi bạn sửa file .env hoặc bị lỗi CORS)
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Reset lại toàn bộ Database và chèn lại dữ liệu mẫu
php artisan migrate:fresh --seed
```

## Dữ liệu mẫu (Seeded Data)

Hệ thống cung cấp sẵn dữ liệu mẫu phục vụ việc test:
- **Tài khoản test chính**: `admin@example.com` (Role: Admin)
- **Tài khoản Editor**: `marie.laurent@example.com` (Role: Editor)
- **Mật khẩu chung cho mọi tài khoản**: `password`
- **Danh mục (Categories)**: Tech, Économie, Environnement, Sport, Culture, Politique.
- **Tin tức (News)**: Có sẵn 14 bài báo tin tức phân bổ vào các danh mục.

## Hỗ trợ (Support)

Nếu quá trình cài đặt gặp lỗi HTTP 500 hoặc truy vấn thất bại:
1. Đảm bảo MySQL service đang chạy.
2. Kiểm tra log hiển thị lỗi chính xác: xem file `storage/logs/laravel.log`.
3. Nhớ clear cache sau mỗi lần đổi `.env` bằng `php artisan config:clear`.
