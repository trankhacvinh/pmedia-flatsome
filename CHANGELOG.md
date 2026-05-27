# Changelog

## 1.2.0

### Changed

- Refactor plugin từ single-file sang multi-file structure.
- Tách code thành các class riêng:
  - `PMFAI_Settings`
  - `PMFAI_Post_Types`
  - `PMFAI_Prompt_Builder`
  - `PMFAI_Code_Validator`
  - `PMFAI_Assets`
  - `PMFAI_REST_API`
  - `PMFAI_Admin_Pages`
  - `PMFAI_Plugin`
- Tách CSS/JS admin và frontend ra thư mục `assets/`.
- Giữ nguyên workflow và hành vi chính của bản 1.1.0.

### Validation

- Đã chạy `php -l` local cho main plugin file và toàn bộ file PHP trong `includes/`, không có lỗi syntax.

## 1.1.0

### Added

- Dashboard mô tả 3 workflow chính: Auto Mode, ChatGPT Bridge, Manual Mode.
- ChatGPT Bridge để tạo prompt chuẩn dùng với ChatGPT web.
- Import From ChatGPT để parse output dạng `pmedia-flatsome-block`.
- Clean / Validate Code để kiểm tra HTML/CSS sinh từ ChatGPT.
- Prompt Library với nhiều prompt mẫu.
- CSS Safety Score và Flatsome Compatibility Score.
- CSS Toolkit layer cho Flatsome với các class `pm-*`.
- Component CSS cơ bản: section, card, hero split, service grid, pricing, process, stats, FAQ, CTA.

### Notes

- Đây là bản MVP nâng cấp theo hướng giảm chi phí API bằng Bridge Mode.
- Source hiện nằm trong `plugin/pmedia-flatsome-ai-toolkit/`.

## 1.0.0

### Added

- Plugin MVP ban đầu.
- Design System settings.
- Frontend CSS Toolkit.
- Settings cho AI API.
