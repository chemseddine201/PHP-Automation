<?php

namespace Services\Interception;


/**
 * Default XHR Request Handler
 * Provides basic logging and modification capabilities
 */

class DefaultXHRHandler implements XHRInterceptorInterface
{
    private array $interceptedRequests = [];
    private array $modificationRules = [];

    public function beforeRequest(array $requestData): array
    {
        $url = $requestData['url'] ?? '';
        $method = $requestData['method'] ?? 'GET';

        echo "[XHR INTERCEPT] Before Request: {$method} {$url}\n";

        // Apply modification rules
        foreach ($this->modificationRules as $rule) {
            if ($rule['type'] === 'before' && $this->matchesPattern($url, $rule['pattern'])) {
                $requestData = array_merge($requestData, $rule['modifications']);
                echo "[XHR MODIFY] Applied rule: {$rule['name']}\n";
            }
        }

        $this->interceptedRequests[] = [
            'type' => 'request',
            'timestamp' => microtime(true),
            'data' => $requestData
        ];

        return $requestData;
    }

    public function afterResponse(array $responseData): array
    {
        $url = $responseData['url'] ?? '';
        $status = $responseData['status'] ?? 0;

        echo "[XHR INTERCEPT] After Response: {$status} {$url}\n";

        // Apply modification rules
        foreach ($this->modificationRules as $rule) {
            if ($rule['type'] === 'after' && $this->matchesPattern($url, $rule['pattern'])) {
                $responseData = array_merge($responseData, $rule['modifications']);
                echo "[XHR MODIFY] Applied rule: {$rule['name']}\n";
            }
        }

        $this->interceptedRequests[] = [
            'type' => 'response',
            'timestamp' => microtime(true),
            'data' => $responseData
        ];

        return $responseData;
    }

    public function shouldIntercept(string $url): bool
    {
        // Default: intercept all XHR requests
        return true;
    }

    public function addModificationRule(string $name, string $pattern, string $type, array $modifications): void
    {
        $this->modificationRules[] = [
            'name' => $name,
            'pattern' => $pattern,
            'type' => $type, // 'before' or 'after'
            'modifications' => $modifications
        ];
    }

    public function getInterceptedRequests(): array
    {
        return $this->interceptedRequests;
    }

    private function matchesPattern(string $url, string $pattern): bool
    {
        return fnmatch($pattern, $url) || preg_match($pattern, $url);
    }
}
