<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Section_Patterns
{
    public static function all(): array
    {
        return [
            'hero-split-premium' => ['name'=>'Hero Split Premium','type'=>'hero','best_for'=>'Landing page, company website, service overview with CTA and visual area.','avoid_when'=>'Very short announcement or dense technical specs.','items'=>'1 headline, 1 lead, 2 CTA, 3 badges/benefits, optional visual card.','copy_rules'=>'Headline <= 12 words. Lead <= 28 words. Badge <= 5 words.','template'=>'Use pm-hero-split with left content, eyebrow, h1, lead, badges, CTA row and right pm-hero-visual/pm-hero-window.'],
            'service-grid-clean' => ['name'=>'Service Grid Clean','type'=>'services','best_for'=>'3-8 services or product categories.','avoid_when'=>'Each service has long description over 35 words.','items'=>'3, 4, 6 or 8 service cards.','copy_rules'=>'Title <= 6 words. Description <= 22 words. Use icon keyword/pill if possible.','template'=>'Use pm-section-head plus pm-service-grid. Each item is pm-service-card with pm-card-icon, pm-card-title, pm-card-text.'],
            'process-horizontal-timeline' => ['name'=>'Horizontal Process Timeline','type'=>'process','best_for'=>'4-8 steps, product journey, workflow, implementation stages.','avoid_when'=>'Step descriptions are very long or require paragraphs.','items'=>'4-8 compact steps with number/icon and short description.','copy_rules'=>'Step title <= 3 words. Description <= 16 words. Prefer horizontal visual flow.','template'=>'Use pm-section-head plus pm-process-timeline. Each step is pm-timeline-step with pm-step-dot number, h3 and short p.'],
            'process-dark-icon-strip' => ['name'=>'Dark Icon Strip','type'=>'process','best_for'=>'Premium visual process similar to product origin/process section.','avoid_when'=>'Plain corporate page without visual emphasis.','items'=>'4-8 icon boxes on dark band.','copy_rules'=>'Very short labels. Description <= 12 words. High contrast, icon-first.','template'=>'Use pm-process-dark plus pm-dark-icon-strip. Each item is pm-dark-icon-item with pm-icon-ring, h3 and compact p.'],
            'feature-proof-grid' => ['name'=>'Feature / Proof Grid','type'=>'benefits','best_for'=>'Benefits, reasons to choose, proof points.','avoid_when'=>'Sequential process steps.','items'=>'3-6 proof/benefit cards.','copy_rules'=>'Title <= 6 words. Description <= 22 words.','template'=>'Use pm-section-soft, pm-section-head and pm-feature-grid. Cards use pm-feature-card, pm-card-icon, pm-card-title, pm-card-text.'],
            'stats-band' => ['name'=>'Stats Band','type'=>'stats','best_for'=>'Numbers, achievements, trust metrics.','avoid_when'=>'No real or plausible metrics available.','items'=>'3-4 metrics.','copy_rules'=>'Metric label <= 5 words. Avoid fake precise numbers unless provided.','template'=>'Use pm-stats. Each metric is pm-stat with pm-stat-number and pm-stat-label.'],
            'pricing-3-cards' => ['name'=>'Pricing 3 Cards','type'=>'pricing','best_for'=>'Three packages or service tiers.','avoid_when'=>'Complex custom pricing.','items'=>'3 pricing cards, one highlighted if appropriate.','copy_rules'=>'Package title <= 4 words. Feature line <= 9 words.','template'=>'Use pm-pricing. Each card is pm-pricing-card with title, pm-price, pm-check-list and CTA.'],
            'faq-simple' => ['name'=>'FAQ Simple','type'=>'faq','best_for'=>'Common objections/questions.','avoid_when'=>'Main selling section.','items'=>'4-8 FAQ items.','copy_rules'=>'Question <= 14 words. Answer <= 32 words.','template'=>'Use pm-faq. Each item is pm-faq-item with pm-faq-question and pm-faq-answer.'],
            'cta-premium-band' => ['name'=>'CTA Premium Band','type'=>'cta','best_for'=>'Final conversion section.','avoid_when'=>'Information-heavy middle section.','items'=>'Headline, short text, 1-2 buttons.','copy_rules'=>'Headline <= 10 words. Lead <= 22 words.','template'=>'Use pm-cta-box with title, lead and action row. Keep it short and conversion-focused.'],
        ];
    }

    public static function prompt_catalog(): string
    {
        $lines = [];
        foreach (self::all() as $id => $pattern) {
            $lines[] = "- {$id}: {$pattern['name']} | type={$pattern['type']} | best_for={$pattern['best_for']} | items={$pattern['items']} | copy_rules={$pattern['copy_rules']} | template={$pattern['template']}";
        }
        return implode("\n", $lines);
    }

    public static function recommend_for_text(string $text): array
    {
        $text_l = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
        $recommended = [];
        if (preg_match('/quy trình|process|workflow|bước|timeline|sản phẩm|thu hoạch|kiểm định|đóng gói|triển khai/u', $text_l)) { $recommended[] = 'process-horizontal-timeline'; $recommended[] = 'process-dark-icon-strip'; }
        if (preg_match('/dịch vụ|service|module|giải pháp|sản phẩm|logistics|spa|bảo hành|quản trị/u', $text_l)) { $recommended[] = 'service-grid-clean'; }
        if (preg_match('/bảng giá|pricing|gói|package/u', $text_l)) { $recommended[] = 'pricing-3-cards'; }
        if (preg_match('/faq|câu hỏi|thắc mắc/u', $text_l)) { $recommended[] = 'faq-simple'; }
        if (preg_match('/liên hệ|tư vấn|cta|đăng ký|nhận báo giá/u', $text_l)) { $recommended[] = 'cta-premium-band'; }
        if (!$recommended) { $recommended = ['hero-split-premium', 'service-grid-clean', 'feature-proof-grid', 'cta-premium-band']; }
        return array_values(array_unique($recommended));
    }
}
