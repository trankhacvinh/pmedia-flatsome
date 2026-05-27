# Pmedia Flatsome AI Toolkit Workflows

Plugin hỗ trợ 3 cách làm việc để phù hợp từng tình huống thực tế.

## 1. Auto Mode

Plugin gọi AI API trực tiếp.

### Khi nào dùng

- Block đơn giản.
- Nhân viên muốn tạo nhanh.
- Không cần phân tích ảnh phức tạp.
- Muốn lưu block vào thư viện nội bộ.

### Luồng dùng

```text
Pmedia AI Builder
→ Generate Block / Auto Mode
→ Nhập mô tả
→ Plugin gọi AI
→ Validate
→ Preview
→ Copy HTML vào Flatsome
```

## 2. ChatGPT Bridge

Plugin tạo prompt chuẩn, ChatGPT web xử lý, plugin import kết quả.

### Khi nào dùng

- Có ảnh mẫu giao diện.
- Cần phân tích layout kỹ.
- Muốn giảm chi phí API plugin.
- Muốn trao đổi nhiều vòng với ChatGPT trước khi đưa vào WordPress.

### Luồng dùng

```text
Pmedia AI Builder → ChatGPT Bridge
→ Chọn loại prompt
→ Copy prompt sang ChatGPT
→ Đính kèm ảnh/mô tả
→ ChatGPT trả về pmedia-flatsome-block
→ Paste vào Import From ChatGPT
→ Validate
→ Copy HTML/CSS
→ Dán vào Flatsome HTML Block
```

### Output contract

ChatGPT cần trả đúng format:

```pmedia-flatsome-block
{
  "title": "Tên block",
  "type": "hero",
  "style": "business",
  "description": "Mô tả ngắn",
  "html": "<section class=\"pm-section pmedia-ai-block\">...</section>",
  "css": "",
  "js": "",
  "notes": ["Ghi chú nếu có"]
}
```

## 3. Manual Mode

Dùng ChatGPT tự do để sinh giao diện, plugin chỉ validate.

### Khi nào dùng

- Đang trao đổi trong ChatGPT.
- Muốn sinh giao diện linh hoạt.
- Muốn tránh gọi API plugin.
- Cần kiểm tra code trước khi dán vào Flatsome.

### Luồng dùng

```text
ChatGPT sinh HTML/CSS
→ Pmedia AI Builder → Clean / Validate Code
→ Paste HTML/CSS
→ Xem CSS Safety Score và Flatsome Compatibility Score
→ Sửa cảnh báo nếu cần
→ Dán vào Flatsome HTML Block
```

## Rule bắt buộc cho HTML/CSS

- Không dùng Tailwind/Bootstrap.
- Không viết CSS global: `body`, `html`, `*`, `a`, `img`, `h1-h6`.
- Không override class Flatsome toàn cục như `.row`, `.col`, `.button`, `.container`, `.section`.
- Không dùng class chung như `.card`, `.title`, `.box`, `.item`.
- Ưu tiên dùng class `pm-*` của toolkit.
- CSS custom nếu có phải scope trong `.pmedia-ai-block`.
- HTML không chứa `html`, `head`, `body` tag.
