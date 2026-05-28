<?php
if (!defined('ABSPATH')) { exit; }

final class PMFAI_OpenAI_Provider implements PMFAI_AI_Provider_Interface
{
    public function complete(array $request)
    {
        $endpoint = trim((string)($request['endpoint'] ?? 'https://api.openai.com/v1/chat/completions'));
        $api_key = trim((string)($request['api_key'] ?? ''));
        if ($api_key === '') {
            return new WP_Error('missing_api_key', 'Chưa cấu hình OpenAI API key.', ['status' => 400]);
        }

        $payload = [
            'model' => $request['model'],
            'temperature' => $request['temperature'],
            'messages' => $request['messages'],
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => (int)($request['timeout'] ?? 90),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) { return $response; }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('api_error', $json['error']['message'] ?? ('AI API error HTTP ' . $code), ['status' => 500, 'http_code' => $code, 'raw' => $body]);
        }

        return [
            'content' => trim((string)($json['choices'][0]['message']['content'] ?? '')),
            'usage' => is_array($json['usage'] ?? null) ? $json['usage'] : [],
            'http_code' => $code,
            'raw' => $json,
        ];
    }
}
