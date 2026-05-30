<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Beauty_Presets
{
    public static function all(): array
    {
        return [
            'corporate_blue' => [
                'name' => 'Corporate Blue',
                'best_for' => 'dịch vụ doanh nghiệp, phần mềm, tư vấn, B2B, landing page nghiêm túc',
                'visual_rules' => 'Nền sáng, xanh/navy làm trục chính, nhiều khoảng thở, card trắng có shadow vừa, CTA rõ ràng, cảm giác tin cậy và chuyên nghiệp.',
                'avoid' => 'Tránh màu quá sặc sỡ, ảnh hoạt hình, gradient quá nhiều, icon trẻ con.',
                'tokens' => ['primary_color'=>'#2563EB','secondary_color'=>'#0F172A','accent_color'=>'#F59E0B','text_color'=>'#1E293B','muted_color'=>'#64748B','border_color'=>'#E2E8F0','bg_soft_color'=>'#F8FAFC','radius_sm'=>'8px','radius_md'=>'16px','radius_lg'=>'26px','section_padding_desktop'=>'78px','section_padding_mobile'=>'44px'],
            ],
            'premium_dark' => [
                'name' => 'Premium Dark',
                'best_for' => 'agency, công nghệ, sản phẩm cao cấp, thương hiệu muốn nhìn sang và mạnh',
                'visual_rules' => 'Nền tối có gradient/radial nhẹ, chữ trắng rõ, accent dùng tiết chế, card glass/dark, CTA nổi bật bằng nút sáng.',
                'avoid' => 'Tránh nền tối toàn trang làm khó đọc, tránh quá nhiều màu neon.',
                'tokens' => ['primary_color'=>'#8B5CF6','secondary_color'=>'#020617','accent_color'=>'#22D3EE','text_color'=>'#E5E7EB','muted_color'=>'#94A3B8','border_color'=>'#1E293B','bg_soft_color'=>'#0F172A','radius_sm'=>'10px','radius_md'=>'18px','radius_lg'=>'30px','section_padding_desktop'=>'86px','section_padding_mobile'=>'48px'],
            ],
            'soft_saas' => [
                'name' => 'Soft SaaS',
                'best_for' => 'SaaS, dashboard, app, hệ thống quản lý, sản phẩm phần mềm thân thiện',
                'visual_rules' => 'Nền sáng/soft, gradient nhẹ, card bo lớn, visual dạng dashboard/mockup, icon line/simple, cảm giác hiện đại và dễ dùng.',
                'avoid' => 'Tránh card dày đặc, màu quá nặng, ảnh người không liên quan.',
                'tokens' => ['primary_color'=>'#4F46E5','secondary_color'=>'#111827','accent_color'=>'#06B6D4','text_color'=>'#1F2937','muted_color'=>'#6B7280','border_color'=>'#E5E7EB','bg_soft_color'=>'#F5F7FB','radius_sm'=>'10px','radius_md'=>'20px','radius_lg'=>'32px','section_padding_desktop'=>'80px','section_padding_mobile'=>'46px'],
            ],
            'luxury_gold' => [
                'name' => 'Luxury Gold',
                'best_for' => 'spa, thẩm mỹ, nội thất, khách sạn, dịch vụ cao cấp',
                'visual_rules' => 'Nền trắng/kem hoặc tối sang, accent vàng/gold, typography thanh lịch, card ít nhưng rộng, nhiều khoảng thở, CTA tinh tế.',
                'avoid' => 'Tránh icon cartoon, màu đỏ/xanh quá mạnh, shadow quá nặng.',
                'tokens' => ['primary_color'=>'#B8892F','secondary_color'=>'#1C1917','accent_color'=>'#E7C873','text_color'=>'#292524','muted_color'=>'#78716C','border_color'=>'#E7E0D3','bg_soft_color'=>'#FBF7EF','radius_sm'=>'10px','radius_md'=>'22px','radius_lg'=>'34px','section_padding_desktop'=>'88px','section_padding_mobile'=>'50px'],
            ],
            'education_friendly' => [
                'name' => 'Education Friendly',
                'best_for' => 'giáo dục, thi trắc nghiệm, trường học, đào tạo, nền tảng học tập',
                'visual_rules' => 'Màu sáng, thân thiện, xanh/teal/blue nhẹ, card rõ ràng, icon học tập, visual dashboard/kết quả/bài thi; chuyên nghiệp nhưng không khô.',
                'avoid' => 'Tránh ảnh cartoon quá trẻ con nếu là hệ thống doanh nghiệp; tránh dark quá nặng.',
                'tokens' => ['primary_color'=>'#0EA5E9','secondary_color'=>'#0F172A','accent_color'=>'#F97316','text_color'=>'#1E293B','muted_color'=>'#64748B','border_color'=>'#DCEAF7','bg_soft_color'=>'#F0F9FF','radius_sm'=>'10px','radius_md'=>'18px','radius_lg'=>'28px','section_padding_desktop'=>'76px','section_padding_mobile'=>'44px'],
            ],
            'medical_clean' => [
                'name' => 'Medical Clean',
                'best_for' => 'y tế, nha khoa, clinic, chăm sóc sức khỏe',
                'visual_rules' => 'Trắng sạch, xanh/teal nhẹ, card rất rõ, ít hiệu ứng, tin cậy, dễ đọc, CTA đặt lịch nổi bật.',
                'avoid' => 'Tránh màu nóng quá mạnh, gradient phức tạp, visual gây rối.',
                'tokens' => ['primary_color'=>'#0F766E','secondary_color'=>'#134E4A','accent_color'=>'#14B8A6','text_color'=>'#1F2937','muted_color'=>'#6B7280','border_color'=>'#D1FAE5','bg_soft_color'=>'#F0FDFA','radius_sm'=>'8px','radius_md'=>'16px','radius_lg'=>'24px','section_padding_desktop'=>'74px','section_padding_mobile'=>'42px'],
            ],
            'tech_gradient' => [
                'name' => 'Tech Gradient',
                'best_for' => 'AI, automation, software, công nghệ mới, data platform',
                'visual_rules' => 'Gradient hiện đại, visual abstract/dashboard, card glass nhẹ, CTA mạnh, section rhythm rõ, có cảm giác công nghệ.',
                'avoid' => 'Tránh quá nhiều glow/neon làm rẻ tiền; không dùng ảnh stock không liên quan.',
                'tokens' => ['primary_color'=>'#7C3AED','secondary_color'=>'#0B1020','accent_color'=>'#06B6D4','text_color'=>'#111827','muted_color'=>'#64748B','border_color'=>'#DDD6FE','bg_soft_color'=>'#F5F3FF','radius_sm'=>'10px','radius_md'=>'20px','radius_lg'=>'34px','section_padding_desktop'=>'86px','section_padding_mobile'=>'48px'],
            ],
            'local_business' => [
                'name' => 'Local Business',
                'best_for' => 'nhà hàng, dịch vụ địa phương, cửa hàng, doanh nghiệp vừa và nhỏ',
                'visual_rules' => 'Thân thiện, rõ dịch vụ, CTA gọi/đặt lịch nổi bật, card dễ hiểu, màu thương hiệu vừa phải, ít thuật ngữ kỹ thuật.',
                'avoid' => 'Tránh layout quá enterprise, chữ quá nhiều, thuật ngữ khó hiểu.',
                'tokens' => ['primary_color'=>'#EA580C','secondary_color'=>'#1F2937','accent_color'=>'#FACC15','text_color'=>'#1F2937','muted_color'=>'#6B7280','border_color'=>'#FED7AA','bg_soft_color'=>'#FFF7ED','radius_sm'=>'8px','radius_md'=>'18px','radius_lg'=>'28px','section_padding_desktop'=>'72px','section_padding_mobile'=>'42px'],
            ],
        ];
    }

    public static function get(string $id = ''): array
    {
        $all = self::all();
        $id = sanitize_key($id ?: (PMFAI_Settings::get_options()['beauty_preset'] ?? 'corporate_blue'));
        return $all[$id] ?? $all['corporate_blue'];
    }

    public static function tokens(string $id = ''): array
    {
        $preset = self::get($id);
        return is_array($preset['tokens'] ?? null) ? $preset['tokens'] : [];
    }

    public static function apply_tokens(string $id): array
    {
        $id = sanitize_key($id ?: 'corporate_blue');
        $tokens = self::tokens($id);
        $options = PMFAI_Settings::get_options();
        foreach ($tokens as $key => $value) { $options[$key] = $value; }
        $options['beauty_preset'] = $id;
        update_option(PMFAI_OPTION_KEY, $options);
        return ['applied' => true, 'preset' => $id, 'tokens' => $tokens, 'options' => $options];
    }

    public static function prompt(string $id = ''): string
    {
        $preset = self::get($id);
        return "Beauty Preset: {$preset['name']}\nBest for: {$preset['best_for']}\nVisual rules: {$preset['visual_rules']}\nAvoid: {$preset['avoid']}";
    }

    public static function options_for_select(): array
    {
        $out = [];
        foreach (self::all() as $id => $preset) { $out[$id] = $preset['name']; }
        return $out;
    }
}
