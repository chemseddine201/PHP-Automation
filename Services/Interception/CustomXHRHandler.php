<?php

namespace Services\Interception;

/**
 * Custom XHR Handler with request-breaking support
 */
class CustomXHRHandler implements XHRInterceptorInterface
{
    private array $requestLog   = [];
    private array $responseLog  = [];
    /** @var callable[] */
    private array $breakRules   = [];

    /* ------------------------------------------------------------
     *  Public API
     * ------------------------------------------------------------ */

    /**
     * Register a breaker rule.
     * Signature: function(array $requestData): bool
     * Return true ⇒ break the request.
     */
    public function addBreaker(callable $rule): self
    {
        $this->breakRules[] = $rule;
        return $this;
    }

    /* ------------------------------------------------------------
     *  XHRInterceptorInterface
     * ------------------------------------------------------------ */

    public function beforeRequest(array $requestData): array
    {
        // 1. Run every registered breaker
        foreach ($this->breakRules as $rule) {
            if ($rule($requestData) === true) {
                $requestData['_break'] = true;   // mark as broken
                break;
            }
        }

        // 2. Normal modification if NOT broken
        if (!($requestData['_break'] ?? false)) {
            if (
                strpos($requestData['url'], 'api.stg.bugs-tracker.com/') !== false &&
                $requestData['body']
            ) {
                $body = json_decode($requestData['body'], true);
                if ($body) {
                    $body['intercepted'] = 1;
                    $body['timestamp']   = time();
                    $requestData['body'] = json_encode($body);
                }
            }
        }

        $this->requestLog[] = $requestData;
        return $requestData;
    }

    public function afterResponse(array $responseData): array
    {
        $this->responseLog[] = $responseData;
        return $responseData;
    }

    public function shouldIntercept(string $url): bool
    {
        return strpos($url, 'api.stg.bugs-tracker.com/api/') !== false;
    }

    /* ------------------------------------------------------------
     *  Helpers
     * ------------------------------------------------------------ */

    public function getRequestLog(): array
    {
        return $this->requestLog;
    }
    public function getResponseLog(): array
    {
        return $this->responseLog;
    }
}
