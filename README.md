# Pmedia Flatsome AI Toolkit

Plugin nội bộ hỗ trợ chuẩn hóa Flatsome Design System, sinh giao diện bằng AI, dùng ChatGPT Bridge để tiết kiệm API, validate CSS và lưu thư viện block tái sử dụng.

## Mục tiêu

Plugin này giúp team Pmedia làm giao diện website WordPress dùng theme Flatsome nhanh hơn nhưng vẫn kiểm soát được CSS.

Thay vì để AI sinh HTML/CSS tự do rồi dán thẳng vào HTML Block, plugin tạo một lớp Design System riêng bằng class `pm-*`, cung cấp prompt chuẩn cho ChatGPT và kiểm tra code trước khi đưa vào Flatsome.

## Tính năng chính

- Design System settings.
- Frontend CSS Toolkit cho Flatsome.
- ChatGPT Bridge để tạo prompt phân tích ảnh/mô tả.
- Import From ChatGPT với format `pmedia-flatsome-block`.
- Clean / Validate Code.
- CSS Safety Score.
- Flatsome Compatibility Score.
- Prompt Library.
- GitHub Actions build plugin zip.

## Source plugin

Source plugin nằm tại:

```text
plugin/pmedia-flatsome-ai-toolkit/
```

## Cài đặt thủ công

Copy thư mục:

```text
plugin/pmedia-flatsome-ai-toolkit/
```

vào:

```text
wp-content/plugins/pmedia-flatsome-ai-toolkit/
```

Sau đó vào WordPress Admin → Plugins → Activate.

## Build file zip

Repo có workflow:

```text
.github/workflows/build-plugin.yml
```

Có thể chạy thủ công bằng GitHub Actions → **Build WordPress Plugin ZIP** → Run workflow.

Artifact tạo ra:

```text
pmedia-flatsome-ai-toolkit.zip
```

## Tài liệu

- `docs/INSTALL.md`: hướng dẫn cài đặt.
- `docs/WORKFLOWS.md`: hướng dẫn 3 workflow sử dụng.
- `docs/ROADMAP.md`: roadmap phát triển.
- `CHANGELOG.md`: lịch sử thay đổi.

## 3 workflow chính

### Auto Mode

Plugin gọi AI API trực tiếp. Phù hợp block đơn giản.

### ChatGPT Bridge

Plugin tạo prompt chuẩn, ChatGPT web xử lý ảnh/mô tả, sau đó paste kết quả về plugin để validate.

### Manual Mode

ChatGPT sinh code hoàn toàn, plugin chỉ kiểm tra CSS Safety và Flatsome Compatibility.
