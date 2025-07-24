<?php

namespace Services\Interception;

/**
 * XHR Request Interceptor Interface
 * Defines the contract for XHR request modification
 */

interface XHRInterceptorInterface
{
    public function beforeRequest(array $requestData): array;
    public function afterResponse(array $responseData): array;
    public function shouldIntercept(string $url): bool;
}
