# GetKey System – Hướng dẫn cài đặt

## Cấu trúc file
```
getkey/
├── config.php          ← DB + Layma config (KHÔNG public)
├── index.php           ← Trang chọn app, lấy key
├── start.php           ← Tạo Layma shortlink → redirect
├── callback.php        ← Sau khi vượt link xong → tạo key
├── done.php            ← Hiển thị key cho user
├── .htaccess           ← Bảo mật
├── api/
│   └── verify.php      ← API xác thực key (dùng trong app)
└── assets/
    ├── css/style.css
    └── js/main.js
```

## Upload lên server
1. Upload toàn bộ thư mục `getkey/` vào thư mục web của bạn
2. Đảm bảo PHP >= 8.0 và extension `mysqli`, `curl` được bật
3. DB và bảng sẽ tự tạo lần đầu chạy (`config.php` → `init_tables()`)

## Thêm ứng dụng mới
Chạy SQL trên DB:
```sql
INSERT INTO apps (app_id, app_name) VALUES ('ten_app', 'Tên App');
```
App sẽ tự hiện ra trong dropdown.

## API xác thực key (dùng trong loader/app)
```
GET /getkey/api/verify.php?key=KEY&hwid=HWID&app_id=kill_cheat
```
Trả về JSON:
```json
{ "status": "valid", "message": "Key hợp lệ", "expires_at": "..." }
{ "status": "invalid", "message": "Key không hợp lệ hoặc đã hết hạn" }
{ "status": "hwid_mismatch", "message": "HWID không khớp" }
```

## Flow hoạt động
```
User mở index.php
  → Chọn app, bấm "Lấy Key 12 Giờ"
  → start.php: tạo session token, gọi Layma API → lấy shortlink
  → Redirect user sang link Layma
  → User vượt link Layma
  → Layma redirect về callback.php?token=TOKEN
  → callback.php: tạo key, lưu DB
  → Redirect sang done.php?key=KEY
  → User copy key dùng trong app
```
