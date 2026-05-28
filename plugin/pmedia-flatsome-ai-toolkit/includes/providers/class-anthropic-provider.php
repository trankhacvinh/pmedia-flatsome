<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_Anthropic_Provider implements PMFAI_AI_Provider_Interface
{
    public function complete(array $request)
    {
        $endpoint = trim((string)($request['endpoint'] ?? 'https://api.anthropic.com/v1/messages'));
        $api_key = trim((string)($request['api_key'] ?? ''));
        if ($api_key === '') {
            return new WP_Error('missing_api_key', 'Chưa cấu hình Anthropic API key.', ['status' => 400]);
        }

        $options = is_array($request['options'] ?? null) ? $request['options'] : PMFAI_Settings::get_options();
        $max_tokens = absint($request['max_tokens'] ?? ($options['anthropic_max_tokens'] ?? 4096));
        $max_tokens = max(512, min(20000, $max_tokens ?: 4096));

        $system = '';
        $messages = [];
        foreach ((array)($request['messages'] ?? []) as $message) {
            $role = $message['role'] ?? 'user';
            $content = (string)($message['content'] ?? '');
            if ($role === 'system') {
                $system .= ($system ? "\n\n" : '') . $content;
                continue;
            }
            $messages[] = [
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'content' => $content,
            ];
        }

        $payload = [
            'model' => $request['model'],
            'max_tokens' => $max_tokens,
            'temperature' => $request['temperature'],
            'messages' => $messages,
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => (int)($request['timeout'] ?? 120),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) { return $response; }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('api_error', $json['error']['message'] ?? ('Anthropic API error HTTP ' . $code), ['status' => 500, 'http_code' => $code, 'raw' => $body]);
        }

        $content = '';
        foreach ((array)($json['content'] ?? []) as $part) {
            if (($part['type'] ?? '') === 'text') {
                $content .= (string)($part['text'] ?? '');
            }
        }

        $usage = [];
        if (is_array($json['usage'] ?? null)) {
            $usage = [
                'prompt_tokens' => (int)($json['usage']['input_tokens'] ?? 0),
                'completion_tokens' => (int)($json['usage']['output_tokens'] ?? 0),
                'total_tokens' => (int)($json['usage']['input_tokens'] ?? 0) + (int)($json['usage']['output_tokens'] ?? 0),
            ];
        }

        return [
            'content' => trim($content),
            'usage' => $usage,
            'http_code' => $code,
            'raw' => $json,
        ];
    }
}
