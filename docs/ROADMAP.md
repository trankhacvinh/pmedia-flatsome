# Pmedia Flatsome AI Toolkit Roadmap

## Mục tiêu

Xây dựng plugin nội bộ giúp Pmedia tạo giao diện Flatsome nhanh bằng AI nhưng vẫn kiểm soát CSS, tái sử dụng Design System và giảm chi phí API bằng workflow kết hợp ChatGPT web.

## 3 chế độ sử dụng

### 1. Auto Mode
Plugin gọi AI API trực tiếp để sinh HTML/CSS. Phù hợp block đơn giản hoặc nhân viên cần thao tác nhanh.

### 2. Bridge Mode
Plugin tạo prompt chuẩn. Người dùng copy sang ChatGPT, đính kèm ảnh/mô tả, sau đó paste kết quả về plugin để parse, validate và lưu block.

### 3. Manual Mode
Người dùng dùng ChatGPT tự do để sinh giao diện, sau đó paste HTML/CSS vào plugin để kiểm tra CSS Safety và Flatsome Compatibility.

## Bản v1.1.0 đã chuẩn bị

- Dashboard workflow.
- ChatGPT Bridge.
- Import From ChatGPT.
- Prompt Library.
- Parser format `pmedia-flatsome-block`.
- Validator có điểm CSS Safety và Flatsome Compatibility.
- Mở rộng component CSS: hero, service grid, pricing, process, stats, FAQ.
- PHP lint đã pass bằng `php -l`.

## Output contract cho ChatGPT

```pmedia-flatsome-block
{
  "title": "Tên block",
  "type": "hero/service/pricing/faq/cta/custom",
  "style": "business",
  "description": "Mô tả ngắn layout",
  "html": "<section class=\"pm-section pmedia-ai-block\">...</section>",
  "css": "",
  "js": "",
  "notes": ["Ghi chú triển khai nếu có"]
}
```

## Ưu tiên tiếp theo

1. Đẩy đầy đủ source plugin dạng multi-file lên repo.
2. Thêm GitHub Actions build zip plugin.
3. Nâng validator thành auto-fix mạnh hơn.
4. Thêm preview desktop/tablet/mobile.
5. Thêm upload ảnh trực tiếp trong plugin ở giai đoạn sau.
