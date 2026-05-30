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
            ],
            'premium_dark' => [
                'name' => 'Premium Dark',
                'best_for' => 'agency, công nghệ, sản phẩm cao cấp, thương hiệu muốn nhìn sang và mạnh',
                'visual_rules' => 'Nền tối có gradient/radial nhẹ, chữ trắng rõ, accent dùng tiết chế, card glass/dark, CTA nổi bật bằng nút sáng.',
                'avoid' => 'Tránh nền tối toàn trang làm khó đọc, tránh quá nhiều màu neon.',
            ],
            'soft_saas' => [
                'name' => 'Soft SaaS',
                'best_for' => 'SaaS, dashboard, app, hệ thống quản lý, sản phẩm phần mềm thân thiện',
                'visual_rules' => 'Nền sáng/soft, gradient nhẹ, card bo lớn, visual dạng dashboard/mockup, icon line/simple, cảm giác hiện đại và dễ dùng.',
                'avoid' => 'Tránh card dày đặc, màu quá nặng, ảnh người không liên quan.',
            ],
            'luxury_gold' => [
                'name' => 'Luxury Gold',
                'best_for' => 'spa, thẩm mỹ, nội thất, khách sạn, dịch vụ cao cấp',
                'visual_rules' => 'Nền trắng/kem hoặc tối sang, accent vàng/gold, typography thanh lịch, card ít nhưng rộng, nhiều khoảng thở, CTA tinh tế.',
                'avoid' => 'Tránh icon cartoon, màu đỏ/xanh quá mạnh, shadow quá nặng.',
            ],
            'education_friendly' => [
                'name' => 'Education Friendly',
                'best_for' => 'giáo dục, thi trắc nghiệm, trường học, đào tạo, nền tảng học tập',
                'visual_rules' => 'Màu sáng, thân thiện, xanh/teal/blue nhẹ, card rõ ràng, icon học tập, visual dashboard/kết quả/bài thi; chuyên nghiệp nhưng không khô.',
                'avoid' => 'Tránh ảnh cartoon quá trẻ con nếu là hệ thống doanh nghiệp; tránh dark quá nặng.',
            ],
            'medical_clean' => [
                'name' => 'Medical Clean',
                'best_for' => 'y tế, nha khoa, clinic, chăm sóc sức khỏe',
                'visual_rules' => 'Trắng sạch, xanh/teal nhẹ, card rất rõ, ít hiệu ứng, tin cậy, dễ đọc, CTA đặt lịch nổi bật.',
                'avoid' => 'Tránh màu nóng quá mạnh, gradient phức tạp, visual gây rối.',
            ],
            'tech_gradient' => [
                'name' => 'Tech Gradient',
                'best_for' => 'AI, automation, software, công nghệ mới, data platform',
                'visual_rules' => 'Gradient hiện đại, visual abstract/dashboard, card glass nhẹ, CTA mạnh, section rhythm rõ, có cảm giác công nghệ.',
                'avoid' => 'Tránh quá nhiều glow/neon làm rẻ tiền; không dùng ảnh stock không liên quan.',
            ],
            'local_business' => [
                'name' => 'Local Business',
                'best_for' => 'nhà hàng, dịch vụ địa phương, cửa hàng, doanh nghiệp vừa và nhỏ',
                'visual_rules' => 'Thân thiện, rõ dịch vụ, CTA gọi/đặt lịch nổi bật, card dễ hiểu, màu thương hiệu vừa phải, ít thuật ngữ kỹ thuật.',
                'avoid' => 'Tránh layout quá enterprise, chữ quá nhiều, thuật ngữ khó hiểu.',
            ],
        ];
    }

    public static function get(string $id = ''): array
    {
        $all = self::all();
        $id = sanitize_key($id ?: (PMFAI_Settings::get_options()['beauty_preset'] ?? 'corporate_blue'));
        return $all[$id] ?? $all['corporate_blue'];
    }

    public static function prompt(string $id = ''): string
    {
        $preset = self::get($id);
        return "Beauty Preset: {$preset['name']}\nBest for: {$preset['best_for']}\nVisual rules: {$preset['visual_rules']}\nAvoid: {$preset['avoid']}";
    }

    public static function options_for_select(): array
    {
        $out = [];
        foreach (self::all() as $id => $preset) {
            $out[$id] = $preset['name'];
        }
        return $out;
    }
}
