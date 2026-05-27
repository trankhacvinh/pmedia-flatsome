<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Prompt_Builder
{
    public static function types(): array
    {
        return [
            'analyze-image' => 'Phân tích ảnh giao diện',
            'generate-from-description' => 'Sinh HTML Block từ mô tả',
            'refactor-code' => 'Refactor HTML/CSS cũ',
            'convert-to-flatsome' => 'Chuyển layout sang Flatsome-compatible',
            'review-css' => 'Review CSS nguy hiểm',
            'ux-builder-guide' => 'Tạo hướng dẫn UX Builder',
        ];
    }

    public static function build(string $type, array $args = []): string
    {
        $args = wp_parse_args($args, [
            'site' => 'Website WordPress dùng theme Flatsome',
            'industry' => 'doanh nghiệp dịch vụ',
            'style' => 'hiện đại, chuyên nghiệp, dễ bán hàng',
            'goal' => 'Tạo giao diện section có thể copy vào Flatsome HTML Block.',
            'content' => '',
        ]);

        $tasks = [
            'analyze-image' => 'Tôi sẽ đính kèm ảnh giao diện mẫu. Hãy phân tích ảnh rồi dựng lại HTML Block tương thích Flatsome.',
            'generate-from-description' => 'Tạo HTML Block dựa trên mô tả sau.',
            'refactor-code' => 'Refactor đoạn HTML/CSS sau về chuẩn toolkit, xóa CSS global nguy hiểm và giảm CSS custom.',
            'convert-to-flatsome' => 'Chuyển layout/mô tả/code sau sang cấu trúc tương thích Flatsome HTML Block.',
            'review-css' => 'Review code sau, chỉ ra lỗi CSS safety, sau đó trả về phiên bản đã sửa.',
            'ux-builder-guide' => 'Tạo hướng dẫn dựng bằng UX Builder và HTML Block mẫu tối thiểu.',
        ];

        return "Bạn là chuyên gia chuyển đổi UI sang HTML Block tương thích WordPress Flatsome.\n\n"
            . "Bối cảnh: {$args['site']}\nNgành nghề: {$args['industry']}\nPhong cách: {$args['style']}\nMục tiêu: {$args['goal']}\n\n"
            . "Class ưu tiên: pm-section, pm-section-soft, pm-section-title, pm-eyebrow, pm-lead, pm-card, pm-feature-list, pm-cta-box, pm-pricing-card, pm-faq, pm-step, pm-hero-split, pm-service-grid, pmedia-ai-block, row, col, col-inner, button primary is-large.\n\n"
            . "Design System token có sẵn, PHẢI dùng lại, không tự khai báo token mới trong từng block:\n"
            . "- var(--pm-color-primary)\n- var(--pm-color-secondary)\n- var(--pm-color-accent)\n- var(--pm-color-text)\n- var(--pm-color-muted)\n- var(--pm-color-border)\n- var(--pm-color-bg-soft)\n- var(--pm-radius-sm)\n- var(--pm-radius-md)\n- var(--pm-radius-lg)\n- var(--pm-section-padding)\n- var(--pm-section-padding-mobile)\n- var(--pm-shadow-sm)\n- var(--pm-shadow-md)\n\n"
            . "Quy tắc bắt buộc:\n"
            . "- Không dùng Tailwind/Bootstrap.\n"
            . "- Không viết CSS global: body, html, *, a, img, h1-h6, .row, .col, .button, .container, .section.\n"
            . "- Ưu tiên dùng class Flatsome và pm-* đã có.\n"
            . "- Nếu cần CSS custom, scope bằng .pmedia-ai-block.\n"
            . "- KHÔNG khai báo CSS variables kiểu --pm-navy, --pm-blue, --pm-text, --pm-muted, --pm-border, --pm-soft trong từng block.\n"
            . "- KHÔNG lặp lại màu hex trong từng block nếu có thể dùng Design System token.\n"
            . "- CSS custom chỉ viết phần layout/hiệu ứng riêng của block, càng ít càng tốt.\n"
            . "- Nếu class pm-* đã đủ dùng thì để css là chuỗi rỗng.\n"
            . "- Không dùng class chung như .card, .title, .box, .item, .wrapper.\n"
            . "- HTML không chứa html/head/body tag.\n"
            . "- Output có wrapper .pmedia-ai-block.\n"
            . "- Trả về đúng một code block markdown language pmedia-flatsome-block, bên trong là JSON hợp lệ gồm title,type,style,description,html,css,js,notes.\n\n"
            . "Nhiệm vụ: " . ($tasks[$type] ?? $tasks['generate-from-description']) . "\n\n"
            . "Nội dung/mô tả/code cần xử lý:\n" . ($args['content'] ?: '[Dán nội dung hoặc đính kèm ảnh ở ChatGPT]') . "\n\n"
            . "Ví dụ output:\n```pmedia-flatsome-block\n{\"title\":\"Tên block\",\"type\":\"hero\",\"style\":\"business\",\"description\":\"Mô tả ngắn\",\"html\":\"<section class=\\\"pm-section pmedia-ai-block\\\">...</section>\",\"css\":\"\",\"js\":\"\",\"notes\":[\"Ghi chú nếu có\"]}\n```";
    }
}
