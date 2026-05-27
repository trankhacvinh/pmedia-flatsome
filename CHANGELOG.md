# Changelog

## 1.8.0

### Added

- Auto Mode / Generate Block thật sự trong admin.
- New class `PMFAI_AI_Service` to call the configured AI API endpoint.
- New admin menu **Generate Block**.
- New REST endpoint `POST /wp-json/pmedia-ai/v1/generate-block`.
- Generate workflow now uses: prompt builder → AI API → pmedia-flatsome-block parser → validator → preview → save to Library.
- Shows raw AI response and parse error when the model does not return the expected format.
- Generated blocks can be saved directly to Block Library.

### Notes

- Requires API key in Settings.
- Bridge Mode and Manual Mode still work without plugin API calls.

## 1.7.0

### Added

- Enhanced Block Library for team reuse.
- Update block metadata and code from the Library detail view.
- Duplicate block action.
- Export block as JSON and copy to clipboard.
- Import block JSON back into Library.
- Filter block list by industry.
- Added `tags` metadata for blocks.
- New REST endpoints:
  - `PUT /wp-json/pmedia-ai/v1/blocks/{id}`
  - `POST /wp-json/pmedia-ai/v1/blocks/{id}/duplicate`
  - `GET /wp-json/pmedia-ai/v1/blocks/{id}/export`
  - `POST /wp-json/pmedia-ai/v1/blocks/import`

## 1.6.0

### Changed

- Preview Sandbox now uses the active Design System tokens from plugin settings.
- `PMFAI_Assets` exposes `tokens` and `tokensCss` to admin JavaScript.
- Preview color, radius and spacing now reflect configured values instead of hard-coded defaults.

### Notes

- Preview still includes lightweight Flatsome class simulation for quick admin checks.
- Final visual QA should still be done in the real Flatsome page/UX Builder.

## 1.5.0

### Added

- Preview Sandbox trong admin bằng `iframe srcdoc`.
- Preview tự hiển thị sau khi:
  - Import From ChatGPT thành công.
  - Validate code.
  - Auto Fix code.
  - Xem chi tiết block trong Block Library.
- Chế độ xem:
  - Desktop
  - Tablet
  - Mobile
- CSS preview được render trong iframe riêng để không ảnh hưởng giao diện WordPress Admin.
- Preview có CSS mô phỏng tối thiểu các class Flatsome và `pm-*`.

### Notes

- Preview chỉ là sandbox mô phỏng nhanh, chưa thay thế việc kiểm tra thật trong Flatsome/UX Builder.
- CSS token trong preview đang dùng default fallback; bản sau có thể đưa token từ Design System hiện tại vào JS.

## 1.4.0

### Added

- Auto Fix CSS/HTML cơ bản trong màn hình **Clean / Validate Code**.
- Class `PMFAI_Code_Auto_Fixer`.
- REST endpoint `POST /wp-json/pmedia-ai/v1/auto-fix-code`.
- Nút **Auto Fix** trong admin.
- Tự thêm wrapper `.pmedia-ai-block` nếu thiếu.
- Tự xóa document tags như `html`, `head`, `body`, `meta`, `title`.
- Tự xóa một số global reset selectors nguy hiểm.
- Tự đổi class chung như `.card`, `.title`, `.badge`, `.cta` sang class `pm-*`.
- Tự scope CSS selector vào `.pmedia-ai-block` ở mức cơ bản.
- Tự thay màu hex khớp Design System sang CSS variables.
- Tự xóa `!important`.

### Notes

- Auto Fix hiện là rule-based, không gọi AI API.
- Cần test thực tế với nhiều block khác nhau trước khi dùng trên site production.

## 1.3.0

### Added

- Block Library thật sự dùng custom post type `pmedia_ai_block`.
- Class `PMFAI_Block_Library` để lưu, lấy danh sách, xem chi tiết và xóa block.
- REST endpoints:
  - `GET /wp-json/pmedia-ai/v1/blocks`
  - `POST /wp-json/pmedia-ai/v1/blocks`
  - `GET /wp-json/pmedia-ai/v1/blocks/{id}`
  - `DELETE /wp-json/pmedia-ai/v1/blocks/{id}`
- Admin menu **Block Library**.
- Nút **Save to Library** sau khi Import From ChatGPT.
- Xem lại block đã lưu, copy HTML/CSS và xóa block.
- Bộ lọc đơn giản theo tên và loại block.

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
