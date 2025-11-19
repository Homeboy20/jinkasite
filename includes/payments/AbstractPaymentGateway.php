<?php

abstract class AbstractPaymentGateway
{
    protected function request(string $method, string $url, $payload = null, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if ($payload !== null) {
            if (is_array($payload)) {
                $body = json_encode($payload);
                $payloadIsJson = true;
            } else {
                $body = (string) $payload;
                $payloadIsJson = false;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            $hasContentType = false;
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $hasContentType = true;
                    break;
                }
            }

            if ($payloadIsJson && !$hasContentType) {
                $headers[] = 'Content-Type: application/json';
            }

            if ($body !== '') {
                $headers[] = 'Content-Length: ' . strlen($body);
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new PaymentGatewayException('HTTP request error: ' . $error);
        }

        $decoded = null;
        if ($response !== '' && $response !== null) {
            $decoded = json_decode($response, true);
        }

        if ($decoded === null && $response !== '' && json_last_error() !== JSON_ERROR_NONE) {
            throw new PaymentGatewayException('Invalid JSON response from gateway.');
        }

        if ($status >= 400) {
            $message = $decoded['error'] ?? $decoded['message'] ?? 'Gateway error';
            throw new PaymentGatewayException($message . ' (HTTP ' . $status . ')');
        }

        if ($decoded !== null) {
            return $decoded;
        }

        return $response === '' ? [] : ['raw' => $response];
    }

    protected function ensureConfigured(array $keys, array $config): void
    {
        foreach ($keys as $key) {
            if (!isset($config[$key]) || trim((string)$config[$key]) === '') {
                throw new PaymentGatewayException('Missing configuration value: ' . $key);
            }
        }
    }

    protected function resolveConfig(string $settingKey, string $constantName = '', string $default = ''): string
    {
        $setting = $this->getSettingValue($settingKey);
        if ($setting !== null && $setting !== '') {
            return $setting;
        }

        if ($constantName !== '' && defined($constantName)) {
            $value = constant($constantName);
            if ($value !== '') {
                return (string) $value;
            }
        }

        return $default;
    }

    protected function isSandboxMode(): bool
    {
        $value = $this->getSettingValue('payment_sandbox_mode');
        if ($value === null || $value === '') {
            return defined('PAYMENT_USE_SANDBOX') ? (bool) PAYMENT_USE_SANDBOX : false;
        }
        $normalized = strtolower((string) $value);
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function getSettingValue(string $key): ?string
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $database = Database::getInstance();
            $connection = $database->getConnection();
            $stmt = $connection->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $key);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result) {
                        $row = $result->fetch_assoc();
                        if ($row && isset($row['setting_value'])) {
                            $cache[$key] = (string) $row['setting_value'];
                            $stmt->close();
                            return $cache[$key];
                        }
                    }
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            // Silently ignore and fall back to defaults
            error_log('Payment setting lookup failed for ' . $key . ': ' . $e->getMessage());
        }

        $cache[$key] = null;
        return null;
    }
}
