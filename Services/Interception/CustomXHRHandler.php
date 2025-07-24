<?php

namespace Services\Interception;

/**
 * Example Custom XHR Handler
 * Demonstrates advanced request modification
 */
class CustomXHRHandler implements XHRInterceptorInterface
{
    private array $requestLog = [];
    private array $responseLog = [];

    public function beforeRequest(array $requestData): array
    {
        $url = $requestData['url'];

        // Example: Modify API requests
        if (strpos($url, '/api/') !== false) {
            // Add custom headers
            $requestData['headers']['X-Custom-Header'] = 'Modified by Interceptor';

            // Modify request body for POST requests
            if ($requestData['method'] === 'POST' && $requestData['body']) {
                $body = json_decode($requestData['body'], true);
                if ($body) {
                    $body['intercepted'] = true;
                    $body['timestamp'] = time();
                    $requestData['body'] = json_encode($body);
                }
            }
        }

        $this->requestLog[] = $requestData;
        return $requestData;
    }

    public function afterResponse(array $responseData): array
    {
        $url = $responseData['url'];

        // Example: Log specific response patterns
        if (strpos($url, '/api/user') !== false && $responseData['status'] === 200) {
            echo "[API] User data retrieved successfully\n";
        }

        // Example: Mock error responses for testing
        if (strpos($url, '/api/test-error') !== false) {
            $responseData['status'] = 500;
            $responseData['statusText'] = 'Mocked Error';
            $responseData['responseText'] = json_encode(['error' => 'Simulated error for testing']);
        }

        $this->responseLog[] = $responseData;
        return $responseData;
    }

    public function shouldIntercept(string $url): bool
    {
        // Only intercept API calls and specific domains
        return strpos($url, '/api/') !== false ||
            strpos($url, 'example.com') !== false ||
            strpos($url, 'test-domain.com') !== false;
    }

    public function getRequestLog(): array
    {
        return $this->requestLog;
    }

    public function getResponseLog(): array
    {
        return $this->responseLog;
    }
}
