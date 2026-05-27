# Changelog

## 2.2.0

### Added

- New admin page **Design System AI**.
- Design System AI supports:
  - Generate from brief via API.
  - Generate ChatGPT Bridge prompt.
  - Import/validate JSON.
  - Preview color tokens/radius/spacing.
  - Apply JSON into Design System settings.
- New `PMFAI_Design_System_AI` class.
- New admin page **Page Builder AI**.
- Page Builder AI supports:
  - Generate full page or single section.
  - Generate ChatGPT Bridge prompt.
  - Import/validate page JSON.
  - Convert page sections into Flatsome `[section] + [row] + [col]` shortcode wrapping HTML blocks.
  - Preview generated sections.
  - Create WordPress Page Draft.
- New `PMFAI_Page_Builder_AI` class.
- New REST endpoints:
  - `POST /wp-json/pmedia-ai/v1/design-system/bridge-prompt`
  - `POST /wp-json/pmedia-ai/v1/design-system/generate`
  - `POST /wp-json/pmedia-ai/v1/design-system/import`
  - `POST /wp-json/pmedia-ai/v1/design-system/apply`
  - `POST /wp-json/pmedia-ai/v1/page-builder/bridge-prompt`
  - `POST /wp-json/pmedia-ai/v1/page-builder/generate`
  - `POST /wp-json/pmedia-ai/v1/page-builder/import`
  - `POST /wp-json/pmedia-ai/v1/page-builder/create-draft`

### Notes

- Page Builder MVP uses the safer strategy: Flatsome section/row/col wrapper + HTML block content.
- Full native Flatsome shortcode generation can be added later after shortcode validation is stronger.

## 2.1.0

### Added

- Usage Log / Cost Tracking foundation.
- New `PMFAI_Usage_Logger` class.
- New custom post type `pmedia_ai_usage` for AI usage records.
- Generate Block now logs user, status, mode, model, block type, industry, duration, HTTP code, tokens and error message.
- New REST endpoint `GET /wp-json/pmedia-ai/v1/usage-logs`.
- New admin page **Usage Logs**.
- Usage summary cards and detailed usage table in admin.

### Notes

- Token fields depend on whether the AI API response includes a `usage` object.
- This is usage tracking foundation; price estimation per model can be added next.

## 2.0.0

### Added

- Per-mode AI model and temperature settings.
- New Settings fields for Fast, Balanced and High Quality model/temperature.
- `PMFAI_AI_Service` now prioritizes per-mode model/temperature settings.

## 1.9.0

### Added

- Cost / Quality Mode for Auto Mode Generate Block.

## 1.8.0

### Added

- Auto Mode / Generate Block thật sự trong admin.

## 1.7.0

### Added

- Enhanced Block Library for team reuse.

## 1.6.0

### Changed

- Preview Sandbox now uses the active Design System tokens from plugin settings.

## 1.5.0

### Added

- Preview Sandbox trong admin bằng `iframe srcdoc`.

## 1.4.0

### Added

- Auto Fix CSS/HTML cơ bản trong màn hình **Clean / Validate Code**.

## 1.3.0

### Added

- Block Library thật sự dùng custom post type `pmedia_ai_block`.

## 1.2.0

### Changed

- Refactor plugin từ single-file sang multi-file structure.

## 1.1.0

### Added

- Dashboard, ChatGPT Bridge, Import From ChatGPT, Clean / Validate Code, Prompt Library, CSS Safety Score, Flatsome Compatibility Score, CSS Toolkit layer.

## 1.0.0

### Added

- Plugin MVP ban đầu.
