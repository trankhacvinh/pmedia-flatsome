<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Page_Builder_Prompt
{
    public static function build(array $params = [], array $schema = []): string
    {
        $brief = trim((string)($params['brief'] ?? ''));
        $mode = sanitize_key($params['buildMode'] ?? 'full-page');
        $output_mode = self::output_mode((string)($params['outputMode'] ?? 'html-block'));
        $schema_json = wp_json_encode($schema ?: PMFAI_Page_Builder_AI::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $recommended_ids = PMFAI_Section_Patterns::recommend_for_text($brief);
        $recommended = implode(', ', $recommended_ids);
        $templates = PMFAI_Section_Pattern_Templates::prompt_templates($recommended_ids);
        $output_rule = $output_mode === 'flatsome-native'
            ? 'Output mode: Flatsome Native Shortcode. Chỉ dùng shortcode thật của Flatsome. Không dùng shortcode tự chế [pm-*].'
            : 'Output mode: Flatsome Section + HTML Block. Đây là mode an toàn và đẹp nhất.';

        return "Bạn là senior UI designer + senior UI engineer cho WordPress Flatsome.\n\n"
            . "Nhiệm vụ: tạo page plan và section theo brief. KHÔNG freestyle layout. Phải chọn pattern/template trước rồi mới điền nội dung.\n"
            . $output_rule . "\n\n"
            . "Pattern Library được phép dùng:\n" . PMFAI_Section_Patterns::prompt_catalog() . "\n\n"
            . "Premium Pattern Templates bắt buộc tham khảo/dùng lại:\n" . $templates . "\n\n"
            . "Pattern được plugin gợi ý từ brief: {$recommended}\n\n"
            . "Visual design quality rules:\n"
            . "- Hero phải có impact: eyebrow, H1 mạnh, lead rõ, CTA chính/phụ, trust badges/proof và visual liên quan ngành.\n"
            . "- Service cards phải có icon, title ngắn, mô tả ngắn, depth/shadow/radius, rhythm đều.\n"
            . "- Process phải khác service, dùng timeline/số thứ tự, nội dung từng bước ngắn.\n"
            . "- Stats/proof phải có nền riêng để phá nhịp trang.\n"
            . "- CTA cuối trang phải mạnh nhất: contrast cao, headline rõ lợi ích, nút lớn, không để chữ chìm.\n"
            . "- Không để toàn bộ page chỉ là card trắng/xanh nhạt giống nhau. Phải có visual rhythm và variation.\n"
            . "- Không dùng ảnh/cartoon/random nếu ngành cần tính doanh nghiệp; nếu chưa có ảnh thật, dùng visual card/gradient/icon thay vì ảnh sai ngữ cảnh.\n"
            . "- Không dùng inline style tràn lan. Không dùng <ux_text> trong HTML Block mode.\n"
            . "- Copy trong card phải ngắn, tự nhiên, chuyên nghiệp, không văn AI.\n\n"
            . "Native Shortcode rules:\n"
            . "- Không dùng bất kỳ shortcode nào bắt đầu bằng [pm- hoặc [/pm-.\n"
            . "- Không viết [title]Nội dung[/title]. Dùng [title text=\"Nội dung\" tag_name=\"h2\"] hoặc [ux_text]<h2>...</h2>[/ux_text].\n"
            . "- Không viết [button]Text[/button]. Dùng [button text=\"Text\" link=\"#contact\" color=\"primary\"].\n"
            . "- Không nested [row]/[col] phức tạp.\n\n"
            . "HTML Block rules:\n"
            . "- HTML phải dùng class pm-* và pmedia-ai-block. Không Bootstrap/Tailwind.\n"
            . "- CSS phải scoped trong .pmedia-ai-block hoặc class section riêng.\n"
            . "- Không dùng script inline, không gọi external assets.\n"
            . "- Không dùng <ux_text>, [row], [col], [section] bên trong field html.\n\n"
            . "Design System token rules:\n"
            . "- Dùng token: var(--pm-color-primary), var(--pm-color-secondary), var(--pm-color-accent), var(--pm-color-text), var(--pm-color-muted), var(--pm-color-border), var(--pm-color-bg-soft), var(--pm-radius-md), var(--pm-radius-lg), var(--pm-shadow-sm), var(--pm-shadow-md).\n"
            . "- Không khai báo biến kiểu --pm-navy, --pm-blue, --pm-text, --pm-muted, --pm-border, --pm-soft trong từng block.\n"
            . "- Nếu class pm-* đã đủ dùng thì css để chuỗi rỗng.\n\n"
            . "JSON rules:\n"
            . "- Trả về duy nhất JSON hợp lệ, không markdown, không giải thích.\n"
            . "- HTML, CSS và shortcode phải nằm trong JSON string hợp lệ.\n"
            . "- Escape dấu nháy kép bên trong HTML/CSS/shortcode đúng một lần: dùng \\\" thay cho dấu nháy kép thô.\n"
            . "- Không double-escape HTML/CSS. Không trả về chuỗi có \\\\n hoặc \\\\\\\" còn hiện ra thành chữ trong preview.\n"
            . "- Nếu build mode là single-section thì chỉ tạo 1 section. Nếu full-page thì tạo đủ page hoàn chỉnh.\n\n"
            . "Build mode: {$mode}\nOutput mode: {$output_mode}\nBrief:\n" . ($brief ?: '[Dán brief trang hoặc website tại đây]') . "\n\n"
            . "Schema bắt buộc:\n" . $schema_json;
    }

    private static function output_mode(string $mode): string
    {
        return $mode === 'flatsome-native' ? 'flatsome-native' : 'html-block';
    }
}
