<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Section_Pattern_Templates
{
    public static function all(): array
    {
        return [
            'hero-split-premium' => [
                'quality_rules' => 'Hero phải có eyebrow, H1 mạnh, lead rõ, 2 CTA, 3 trust badges, visual card liên quan ngành. Không dùng ảnh/cartoon random nếu không được yêu cầu.',
                'anti_patterns' => 'Không để hero quá trắng/trống; không dùng ảnh placeholder; không để CTA xa headline; không dùng inline style.',
                'html' => '<section class="pm-section pmedia-ai-block pm-hero-premium"><div class="pm-hero-split"><div class="pm-hero-content"><span class="pm-eyebrow">[EYEBROW]</span><h1 class="pm-title">[HEADLINE]</h1><p class="pm-lead">[LEAD]</p><div class="pm-hero-badges"><span class="pm-pill pm-pill-primary">[BADGE 1]</span><span class="pm-pill">[BADGE 2]</span><span class="pm-pill">[BADGE 3]</span></div><div class="pm-action-row"><a class="button primary is-large" href="#contact">[PRIMARY CTA]</a><a class="pm-button-outline" href="#services">[SECONDARY CTA]</a></div></div><div class="pm-hero-visual"><div class="pm-hero-window"><div class="pm-visual-kicker">[VISUAL LABEL]</div><h3>[VISUAL TITLE]</h3><p>[VISUAL DESCRIPTION]</p><div class="pm-mini-stats"><span><strong>[STAT 1]</strong>[LABEL 1]</span><span><strong>[STAT 2]</strong>[LABEL 2]</span></div></div></div></div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-hero-split-premium"]\n[row v_align="middle"]\n[col span="6" span__sm="12"]\n[ux_text]<p class="pm-eyebrow">[EYEBROW]</p><h1>[HEADLINE]</h1><p class="pm-lead">[LEAD]</p><div class="pm-hero-badges"><span class="pm-pill pm-pill-primary">[BADGE 1]</span><span class="pm-pill">[BADGE 2]</span><span class="pm-pill">[BADGE 3]</span></div>[/ux_text]\n[button text="[PRIMARY CTA]" color="primary" size="large" link="#contact"] [button text="[SECONDARY CTA]" style="outline" link="#services"]\n[/col]\n[col span="6" span__sm="12"]\n[ux_text]<div class="pm-hero-window"><div class="pm-visual-kicker">[VISUAL LABEL]</div><h3>[VISUAL TITLE]</h3><p>[VISUAL DESCRIPTION]</p></div>[/ux_text]\n[/col]\n[/row]\n[/section]'
            ],
            'service-premium-cards' => [
                'quality_rules' => 'Dùng cho 3-6 dịch vụ. Mỗi card có icon, title ngắn, mô tả 1 câu, mini link/label. Cards phải có depth, rhythm và khoảng cách đều.',
                'anti_patterns' => 'Không viết mô tả dài; không dùng 4 card quá rộng nếu title dài; không dùng toàn icon emoji thiếu ngữ cảnh.',
                'html' => '<section class="pm-section pm-section-soft pmedia-ai-block pm-services-premium"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-service-grid">[REPEAT 3-6: <article class="pm-service-card"><div class="pm-card-icon">[ICON]</div><h3 class="pm-card-title">[SERVICE TITLE]</h3><p class="pm-card-text">[SHORT DESCRIPTION]</p><span class="pm-card-link">[MINI LABEL]</span></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-service-premium-cards"]\n[row]\n[col span__sm="12" align="center"]\n[ux_text]<p class="pm-eyebrow">[EYEBROW]</p><h2>[TITLE]</h2><p class="pm-lead">[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[REPEAT 3-6: [col span="4" span__sm="12"][ux_text]<article class="pm-service-card"><div class="pm-card-icon">[ICON]</div><h3 class="pm-card-title">[SERVICE TITLE]</h3><p class="pm-card-text">[SHORT DESCRIPTION]</p><span class="pm-card-link">[MINI LABEL]</span></article>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'process-premium-timeline' => [
                'quality_rules' => 'Dùng cho 4-6 bước. Mỗi bước rất ngắn, có số thứ tự rõ, khác section dịch vụ. Tạo cảm giác quy trình có kiểm soát.',
                'anti_patterns' => 'Không viết đoạn văn dài trong bước; không dùng card hẹp làm chữ rơi dọc; không lặp style service card.',
                'html' => '<section class="pm-section pmedia-ai-block pm-process-premium"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-process-timeline">[REPEAT 4-6: <article class="pm-timeline-step"><div class="pm-step-dot">[01]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-process-premium-timeline"]\n[row]\n[col span__sm="12" align="center"]\n[ux_text]<p class="pm-eyebrow">[EYEBROW]</p><h2>[TITLE]</h2><p class="pm-lead">[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[REPEAT 4: [col span="3" span__sm="12"][ux_text]<article class="pm-timeline-step"><div class="pm-step-dot">[01]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></article>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'stats-proof-band' => [
                'quality_rules' => 'Dùng cho proof/trust. Chỉ dùng số liệu được cung cấp hoặc số liệu dạng mềm như 24/7, 3 bước, 100% hỗ trợ. Tạo nền riêng để phá nhịp trang.',
                'anti_patterns' => 'Không bịa số quá cụ thể; không đặt stats trôi nổi không có ngữ cảnh.',
                'html' => '<section class="pm-section pm-section-soft pmedia-ai-block pm-proof-band"><div class="pm-proof-inner"><div class="pm-proof-copy"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-stats">[REPEAT 3-4: <div class="pm-stat"><span class="pm-stat-number">[NUMBER]</span><span class="pm-stat-label">[LABEL]</span></div>]</div></div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-stats-proof-band"]\n[row v_align="middle"]\n[col span="5" span__sm="12"]\n[ux_text]<p class="pm-eyebrow">[EYEBROW]</p><h2>[TITLE]</h2><p class="pm-lead">[LEAD]</p>[/ux_text]\n[/col]\n[col span="7" span__sm="12"]\n[ux_text]<div class="pm-stats">[REPEAT: <div class="pm-stat"><span class="pm-stat-number">[NUMBER]</span><span class="pm-stat-label">[LABEL]</span></div>]</div>[/ux_text]\n[/col]\n[/row]\n[/section]'
            ],
            'cta-premium-conversion' => [
                'quality_rules' => 'CTA cuối trang phải mạnh, contrast cao, headline rõ lợi ích, lead ngắn, 1 CTA chính và 1 phụ nếu cần. Đây là section chuyển đổi.',
                'anti_patterns' => 'Không để chữ chìm trên nền; không dùng nút nhỏ; không dùng CTA chung chung như Xem thêm.',
                'html' => '<section class="pm-section pmedia-ai-block pm-cta-premium"><div class="pm-cta-box"><div class="pm-cta-content"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-action-row"><a class="button primary is-large" href="#contact">[PRIMARY CTA]</a><a class="pm-button-light" href="#services">[SECONDARY CTA]</a></div></div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-cta-premium-conversion"]\n[row]\n[col span__sm="12"]\n[ux_text]<div class="pm-cta-box"><div class="pm-cta-content"><p class="pm-eyebrow">[EYEBROW]</p><h2>[TITLE]</h2><p class="pm-lead">[LEAD]</p></div></div>[/ux_text]\n[button text="[PRIMARY CTA]" color="primary" size="large" link="#contact"] [button text="[SECONDARY CTA]" style="outline" link="#services"]\n[/col]\n[/row]\n[/section]'
            ],
            'faq-premium-simple' => [
                'quality_rules' => 'Dùng cho 4-6 câu hỏi xử lý phản đối mua hàng. Câu trả lời ngắn, rõ, tự nhiên.',
                'anti_patterns' => 'Không đưa FAQ quá dài; không lặp lại nội dung service; không dùng câu trả lời chung chung.',
                'html' => '<section class="pm-section pmedia-ai-block pm-faq-premium"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-faq">[REPEAT 4-6: <details class="pm-faq-item"><summary class="pm-faq-question">[QUESTION]</summary><div class="pm-faq-answer">[ANSWER]</div></details>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-faq-premium-simple"]\n[row]\n[col span__sm="12" align="center"]\n[ux_text]<p class="pm-eyebrow">[EYEBROW]</p><h2>[TITLE]</h2><p class="pm-lead">[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[col span__sm="12"]\n[accordion]\n[REPEAT 4-6: [accordion-item title="[QUESTION]"][ANSWER][/accordion-item]]\n[/accordion]\n[/col]\n[/row]\n[/section]'
            ],
        ];
    }

    public static function prompt_templates(array $ids = []): string
    {
        $all = self::all();
        if (!$ids) { $ids = array_keys($all); }
        $chunks = [];
        foreach ($ids as $id) {
            if (!isset($all[$id])) { continue; }
            $p = $all[$id];
            $chunks[] = "Pattern {$id}\nQuality rules: {$p['quality_rules']}\nAnti-patterns: {$p['anti_patterns']}\nHTML template:\n{$p['html']}\nNative shortcode template:\n{$p['shortcode']}";
        }
        return implode("\n\n---\n\n", $chunks);
    }
}
