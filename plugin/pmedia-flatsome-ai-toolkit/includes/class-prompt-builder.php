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
        if (!empty($args['beautyPreset']) && empty($args['beauty_preset'])) {
            $args['beauty_preset'] = $args['beautyPreset'];
        }

        $args = wp_parse_args($args, [
            'site' => 'Website WordPress dùng theme Flatsome',
            'industry' => 'doanh nghiệp dịch vụ',
            'style' => 'hiện đại, chuyên nghiệp, dễ bán hàng',
            'goal' => 'Tạo giao diện section có thể copy vào Flatsome HTML Block.',
            'content' => '',
            'output_mode' => 'html-block',
            'beauty_preset' => PMFAI_Settings::get_options()['beauty_preset'] ?? 'corporate_blue',
        ]);

        $tasks = [
            'analyze-image' => 'Tôi sẽ đính kèm ảnh giao diện mẫu. Hãy phân tích ảnh rồi dựng lại HTML Block tương thích Flatsome.',
            'generate-from-description' => 'Tạo HTML Block dựa trên mô tả sau.',
            'refactor-code' => 'Refactor đoạn HTML/CSS sau về chuẩn toolkit, xóa CSS global nguy hiểm và giảm CSS custom.',
            'convert-to-flatsome' => 'Chuyển layout/mô tả/code sau sang cấu trúc tương thích Flatsome HTML Block.',
            'review-css' => 'Review code sau, chỉ ra lỗi CSS safety, sau đó trả về phiên bản đã sửa.',
            'ux-builder-guide' => 'Tạo hướng dẫn dựng bằng UX Builder và HTML Block mẫu tối thiểu.',
        ];

        $premium_templates = PMFAI_Section_Pattern_Templates::prompt_templates();
        $beauty_prompt = PMFAI_Beauty_Presets::prompt(sanitize_key((string)$args['beauty_preset']));

        $prompt = "Bạn là senior UI designer và senior UI engineer cho WordPress Flatsome.\n\n"
            . "Bối cảnh: {$args['site']}\nNgành nghề: {$args['industry']}\nPhong cách: {$args['style']}\nMục tiêu: {$args['goal']}\n\n"
            . "Beauty Preset bắt buộc tuân theo:\n" . $beauty_prompt . "\n\n"
            . "QUY TẮC QUAN TRỌNG: Không freestyle layout. Phải chọn một Premium Pattern Template phù hợp rồi điền nội dung vào template.\n\n"
            . "Premium Pattern Templates bắt buộc dùng/tham khảo:\n" . $premium_templates . "\n\n"
            . "Visual quality rules:\n"
            . "- Toàn block/section phải thể hiện đúng Beauty Preset, không pha gu lung tung.\n"
            . "- Hero phải có eyebrow, H1 mạnh, lead rõ, CTA chính/phụ, trust badges/proof và visual liên quan ngành.\n"
            . "- Service cards phải có icon, title ngắn, mô tả ngắn, depth/shadow/radius và rhythm đều.\n"
            . "- Process phải khác service, dùng timeline/số thứ tự, mô tả từng bước ngắn.\n"
            . "- Stats/proof phải có nền riêng để phá nhịp trang.\n"
            . "- CTA phải nổi bật, contrast cao, headline rõ lợi ích, nút lớn và dễ bấm.\n"
            . "- Không dùng ảnh/cartoon/random nếu ngành cần tính doanh nghiệp; nếu chưa có ảnh thật, dùng visual card/gradient/icon.\n"
            . "- Không để toàn section là card trắng/xanh nhạt giống nhau.\n\n"
            . "Class ưu tiên: pm-section, pm-section-soft, pm-section-head, pm-eyebrow, pm-lead, pm-title, pm-card, pm-service-card, pm-card-icon, pm-card-title, pm-card-text, pm-cta-box, pm-hero-split, pm-hero-window, pm-service-grid, pm-process-timeline, pm-timeline-step, pm-step-dot, pm-stats, pm-stat, pmedia-ai-block, button primary is-large.\n\n"
            . "Design System token có sẵn, PHẢI dùng lại, không tự khai báo token mới trong từng block:\n"
            . "- var(--pm-color-primary)\n- var(--pm-color-secondary)\n- var(--pm-color-accent)\n- var(--pm-color-text)\n- var(--pm-color-muted)\n- var(--pm-color-border)\n- var(--pm-color-bg-soft)\n- var(--pm-radius-sm)\n- var(--pm-radius-md)\n- var(--pm-radius-lg)\n- var(--pm-section-padding)\n- var(--pm-section-padding-mobile)\n- var(--pm-shadow-sm)\n- var(--pm-shadow-md)\n\n"
            . "Quy tắc bắt buộc:\n"
            . "- Không dùng Tailwind/Bootstrap.\n"
            . "- Không viết CSS global: body, html, *, a, img, h1-h6, .row, .col, .button, .container, .section.\n"
            . "- Không dùng class chung như .card, .title, .box, .item, .wrapper.\n"
            . "- Không dùng <ux_text>, [row], [col], [section] trong HTML Block.\n"
            . "- Không dùng inline style tràn lan.\n"
            . "- Nếu cần CSS custom, scope bằng .pmedia-ai-block hoặc class section riêng.\n"
            . "- Nếu class pm-* đã đủ dùng thì để css là chuỗi rỗng.\n"
            . "- Output có wrapper .pmedia-ai-block.\n"
            . "- Trả về đúng một code block markdown language pmedia-flatsome-block, bên trong là JSON hợp lệ gồm title,type,style,description,html,css,js,notes.\n\n"
            . "Nhiệm vụ: " . ($tasks[$type] ?? $tasks['generate-from-description']) . "\n\n"
            . "Nội dung/mô tả/code cần xử lý:\n" . ($args['content'] ?: '[Dán nội dung hoặc đính kèm ảnh ở ChatGPT]') . "\n\n"
            . "Ví dụ output:\n```pmedia-flatsome-block\n{\"title\":\"Tên block\",\"type\":\"hero\",\"style\":\"business\",\"description\":\"Mô tả ngắn\",\"html\":\"<section class=\\\"pm-section pmedia-ai-block pm-hero-premium\\\">...</section>\",\"css\":\"\",\"js\":\"\",\"notes\":[\"Ghi chú nếu có\"]}\n```";

        return PMFAI_Flatsome_UI_Skill::enhance_prompt($prompt, sanitize_key((string)$args['output_mode']), 'bridge-block');
    }
}
