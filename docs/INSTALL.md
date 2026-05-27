# Hướng dẫn cài đặt Pmedia Flatsome AI Toolkit

## Cài từ source repo

Source plugin nằm tại:

```text
plugin/pmedia-flatsome-ai-toolkit/
```

Cách cài:

1. Copy thư mục `plugin/pmedia-flatsome-ai-toolkit` vào:

```text
wp-content/plugins/pmedia-flatsome-ai-toolkit
```

2. Vào WordPress Admin → Plugins.
3. Activate **Pmedia Flatsome AI Toolkit**.
4. Vào menu **Pmedia AI Builder**.

## Workflow khuyên dùng

### Cách 1: Auto Mode

Dùng plugin gọi AI trực tiếp. Cần cấu hình API key trong Settings.

### Cách 2: ChatGPT Bridge

1. Vào **Pmedia AI Builder → ChatGPT Bridge**.
2. Chọn loại prompt, ví dụ `Phân tích ảnh giao diện`.
3. Copy prompt sang ChatGPT.
4. Đính kèm ảnh hoặc mô tả.
5. Copy kết quả dạng `pmedia-flatsome-block`.
6. Paste vào **Import From ChatGPT**.
7. Validate và copy HTML vào Flatsome HTML Block.

### Cách 3: Manual Mode

1. Dùng ChatGPT sinh HTML/CSS theo prompt chuẩn.
2. Paste vào **Clean / Validate Code**.
3. Kiểm tra CSS Safety và Flatsome Compatibility.
4. Copy HTML/CSS đã đạt yêu cầu vào Flatsome.

## Ghi chú

- Không nên sửa core theme Flatsome.
- Plugin tạo CSS layer riêng dùng class `pm-*`.
- CSS custom từ AI nên được scope trong `.pmedia-ai-block`.
