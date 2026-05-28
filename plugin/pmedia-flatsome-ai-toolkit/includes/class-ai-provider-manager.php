<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_AI_Provider_Manager
{
    public static function provider_id(array $options = []): string
    {
        $options = $options ?: PMFAI_Settings::get_options();
        $provider = sanitize_key($options['ai_provider'] ?? 'openai');
        return in_array($provider, ['openai', 'openai_compatible', 'anthropic'], true) ? $provider : 'openai';
    }

    public static function complete(array $request)
    {
        $options = $request['options'] ?? PMFAI_Settings::get_options();
        $provider_id = sanitize_key($request['provider'] ?? self::provider_id($options));
        $provider = self::provider($provider_id);
        if (is_wp_error($provider)) { return $provider; }

        $request['provider'] = $provider_id;
        $request['endpoint'] = self::endpoint($provider_id, $options, $request);
        $request['api_key'] = self::api_key($provider_id, $options, $request);
        $request['model'] = self::model($provider_id, $options, $request);

        return $provider->complete($request);
    }

    public static function endpoint(string $provider_id, array $options, array $request = []): string
    {
        if (!empty($request['endpoint'])) { return (string)$request['endpoint']; }
        if ($provider_id === 'anthropic') { return trim((string)($options['anthropic_endpoint'] ?? '')) ?: 'https://api.anthropic.com/v1/messages'; }
        if ($provider_id === 'openai_compatible') { return trim((string)($options['compatible_endpoint'] ?? '')) ?: 'https://api.openai.com/v1/chat/completions'; }
        return trim((string)($options['api_endpoint'] ?? '')) ?: 'https://api.openai.com/v1/chat/completions';
    }

    public static function api_key(string $provider_id, array $options, array $request = []): string
    {
        if (!empty($request['api_key'])) { return (string)$request['api_key']; }
        if ($provider_id === 'anthropic') { return trim((string)($options['anthropic_api_key'] ?? '')); }
        if ($provider_id === 'openai_compatible') { return trim((string)($options['compatible_api_key'] ?? '')); }
        return trim((string)($options['api_key'] ?? ''));
    }

    public static function model(string $provider_id, array $options, array $request = []): string
    {
        $requested = trim((string)($request['model'] ?? ''));
        if ($requested !== '' && self::is_model_compatible($provider_id, $requested)) {
            return $requested;
        }
        if ($provider_id === 'anthropic') { return trim((string)($options['anthropic_model'] ?? '')) ?: 'claude-3-5-sonnet-latest'; }
        if ($provider_id === 'openai_compatible') { return trim((string)($options['compatible_model'] ?? '')) ?: (trim((string)($options['api_model'] ?? '')) ?: 'gpt-4.1-mini'); }
        return trim((string)($options['api_model'] ?? '')) ?: 'gpt-4.1-mini';
    }

    public static function provider_label(string $provider_id): string
    {
        $labels = [
            'openai' => 'OpenAI / ChatGPT',
            'openai_compatible' => 'OpenAI-compatible',
            'anthropic' => 'Anthropic Claude',
        ];
        return $labels[$provider_id] ?? $provider_id;
    }

    public static function is_model_compatible(string $provider_id, string $model): bool
    {
        $model = strtolower(trim($model));
        if ($model === '') { return false; }
        if ($provider_id === 'anthropic') {
            return strpos($model, 'claude') === 0;
        }
        if ($provider_id === 'openai' || $provider_id === 'openai_compatible') {
            return strpos($model, 'claude') !== 0;
        }
        return true;
    }

    private static function provider(string $provider_id)
    {
        if ($provider_id === 'anthropic') { return new PMFAI_Anthropic_Provider(); }
        if ($provider_id === 'openai_compatible') { return new PMFAI_OpenAI_Provider(); }
        if ($provider_id === 'openai') { return new PMFAI_OpenAI_Provider(); }
        return new WP_Error('invalid_provider', 'AI provider không hợp lệ.', ['status' => 400]);
    }
}
