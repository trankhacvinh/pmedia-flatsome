<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Section_Pattern_Templates
{
    public static function all(): array
    {
        return [
            'hero-split-premium' => [
                'html' => '<section class="pm-section pmedia-ai-block pm-hero-split"><div class="pm-hero-content"><span class="pm-eyebrow">[EYEBROW]</span><h1 class="pm-title">[HEADLINE]</h1><p class="pm-lead">[LEAD]</p><div class="pm-hero-badges"><span class="pm-pill pm-pill-primary">[BADGE 1]</span><span class="pm-pill">[BADGE 2]</span><span class="pm-pill">[BADGE 3]</span></div><div class="pm-action-row"><a class="button primary" href="#contact">[PRIMARY CTA]</a><a class="pm-button-outline" href="#services">[SECONDARY CTA]</a></div></div><div class="pm-hero-visual"><div class="pm-hero-window">[VISUAL SUMMARY]</div></div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-hero-split-premium"]\n[row v_align="middle"]\n[col span="6" span__sm="12"]\n[ux_text]\n<p class="pm-eyebrow">[EYEBROW]</p><h1>[HEADLINE]</h1><p>[LEAD]</p>\n[/ux_text]\n[button text="[PRIMARY CTA]" color="primary" size="large" link="#contact"] [button text="[SECONDARY CTA]" style="outline" link="#services"]\n[/col]\n[col span="6" span__sm="12"]\n[ux_text]\n<div class="pm-hero-window">[VISUAL SUMMARY]</div>\n[/ux_text]\n[/col]\n[/row]\n[/section]'
            ],
            'service-grid-clean' => [
                'html' => '<section class="pm-section pmedia-ai-block"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-service-grid">[REPEAT SERVICE CARD: <article class="pm-service-card"><div class="pm-card-icon">[ICON]</div><h3 class="pm-card-title">[SERVICE TITLE]</h3><p class="pm-card-text">[SHORT DESCRIPTION]</p></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-service-grid-clean"]\n[row]\n[col span__sm="12" align="center"]\n[title text="[TITLE]" tag_name="h2"]\n[ux_text]<p>[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[REPEAT: [col span="4" span__sm="12"][featured_box img_width="48" pos="center" title="[SERVICE TITLE]"] [SHORT DESCRIPTION] [/featured_box][/col]]\n[/row]\n[/section]'
            ],
            'process-horizontal-timeline' => [
                'html' => '<section class="pm-section pmedia-ai-block"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-process-timeline">[REPEAT STEP: <article class="pm-timeline-step"><div class="pm-step-dot">[01]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-process-horizontal-timeline"]\n[row]\n[col span__sm="12" align="center"]\n[title text="[TITLE]" tag_name="h2"]\n[ux_text]<p>[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[REPEAT: [col span="3" span__sm="12"][ux_text]<div class="pm-timeline-step"><div class="pm-step-dot">[01]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></div>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'process-dark-icon-strip' => [
                'html' => '<section class="pm-section pmedia-ai-block pm-process-dark"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-dark-icon-strip">[REPEAT STEP: <article class="pm-dark-icon-item"><div class="pm-icon-ring">[ICON]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-process-dark-icon-strip pm-process-dark"]\n[row]\n[col span__sm="12" align="center"]\n[title text="[TITLE]" tag_name="h2"]\n[/col]\n[/row]\n[row]\n[REPEAT: [col span="3" span__sm="12"][ux_text]<div class="pm-dark-icon-item"><div class="pm-icon-ring">[ICON]</div><h3>[STEP TITLE]</h3><p>[SHORT DESCRIPTION]</p></div>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'feature-proof-grid' => [
                'html' => '<section class="pm-section pm-section-soft pmedia-ai-block"><div class="pm-section-head"><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-feature-grid">[REPEAT FEATURE: <article class="pm-feature-card"><div class="pm-card-icon">[ICON]</div><h3 class="pm-card-title">[FEATURE TITLE]</h3><p class="pm-card-text">[SHORT DESCRIPTION]</p></article>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-feature-proof-grid"]\n[row]\n[col span__sm="12" align="center"]\n[title text="[TITLE]" tag_name="h2"]\n[ux_text]<p>[LEAD]</p>[/ux_text]\n[/col]\n[/row]\n[row]\n[REPEAT: [col span="4" span__sm="12"][ux_text]<div class="pm-feature-card"><h3>[FEATURE TITLE]</h3><p>[SHORT DESCRIPTION]</p></div>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'stats-band' => [
                'html' => '<section class="pm-section pm-section-soft pmedia-ai-block"><div class="pm-stats">[REPEAT STAT: <div class="pm-stat"><span class="pm-stat-number">[NUMBER]</span><span class="pm-stat-label">[LABEL]</span></div>]</div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-stats-band"]\n[row]\n[REPEAT: [col span="3" span__sm="6"][ux_text]<div class="pm-stat"><span class="pm-stat-number">[NUMBER]</span><span class="pm-stat-label">[LABEL]</span></div>[/ux_text][/col]]\n[/row]\n[/section]'
            ],
            'cta-premium-band' => [
                'html' => '<section class="pm-section pmedia-ai-block"><div class="pm-cta-box"><div><span class="pm-eyebrow">[EYEBROW]</span><h2 class="pm-title">[TITLE]</h2><p class="pm-lead">[LEAD]</p></div><div class="pm-action-row"><a class="button primary" href="#contact">[CTA]</a></div></div></section>',
                'shortcode' => '[section class="pm-native-section pm-pattern-cta-premium-band"]\n[row]\n[col span__sm="12"]\n[ux_text]<div class="pm-cta-box"><div><h2>[TITLE]</h2><p>[LEAD]</p></div></div>[/ux_text]\n[button text="[CTA]" color="primary" size="large" link="#contact"]\n[/col]\n[/row]\n[/section]'
            ],
        ];
    }

    public static function prompt_templates(array $ids = []): string
    {
        $all = self::all();
        if (!$ids) {
            $ids = array_keys($all);
        }
        $chunks = [];
        foreach ($ids as $id) {
            if (!isset($all[$id])) {
                continue;
            }
            $chunks[] = "Pattern {$id}\nHTML template:\n" . $all[$id]['html'] . "\nNative shortcode template:\n" . $all[$id]['shortcode'];
        }
        return implode("\n\n---\n\n", $chunks);
    }
}
