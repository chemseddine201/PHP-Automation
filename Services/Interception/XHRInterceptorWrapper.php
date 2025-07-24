<?php

namespace Services\Interception;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverWait;

class XHRInterceptorWrapper
{
    private RemoteWebDriver $driver;
    private XHRInterceptorInterface $xhrHandler;
    private bool $interceptorInstalled = false;
    private string $interceptorScript;

    public function __construct(RemoteWebDriver $driver, XHRInterceptorInterface $xhrHandler = null)
    {
        $this->driver = $driver;
        $this->xhrHandler = $xhrHandler ?: new DefaultXHRHandler();
        $this->initializeInterceptorScript();
    }

    /**
     * Delegate all calls to the original driver
     */
    public function __call($method, $args)
    {
        // Intercept the get() method to auto-install interceptor
        if ($method === 'get') {
            $result = $this->driver->$method(...$args);
            usleep(100000); // 0.1 seconds
            $this->installXHRInterceptor();
            return $result;
        }

        return $this->driver->$method(...$args);
    }

    /**
     * Get the underlying driver for direct access
     */
    public function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }

    /**
     * Initialize the JavaScript interceptor script
     */
    private function initializeInterceptorScript(): void
    {
        $this->interceptorScript = "
            (function() {
                if (window.__xhrInterceptorInstalled) return;
                window.__xhrInterceptorInstalled = true;
                
                // Store original XMLHttpRequest
                var OriginalXHR = window.XMLHttpRequest;
                var interceptedRequests = [];
                
                // Override XMLHttpRequest
                window.XMLHttpRequest = function() {
                    var xhr = new OriginalXHR();
                    var originalOpen = xhr.open;
                    var originalSend = xhr.send;
                    var originalSetRequestHeader = xhr.setRequestHeader;
                    
                    var requestData = {
                        url: '',
                        method: 'GET',
                        headers: {},
                        body: null,
                        timestamp: Date.now()
                    };
                    
                    // Override open method
                    xhr.open = function(method, url, async, user, password) {
                        requestData.method = method;
                        requestData.url = url;
                        requestData.async = async !== false;
                        
                        return originalOpen.apply(this, arguments);
                    };
                    
                    // Override setRequestHeader method
                    xhr.setRequestHeader = function(header, value) {
                        requestData.headers[header] = value;
                        return originalSetRequestHeader.apply(this, arguments);
                    };
                    
                    // Override send method
                    xhr.send = function(body) {
                        requestData.body = body;
                        
                        // Store request for processing
                        window.__pendingXHRRequests = window.__pendingXHRRequests || [];
                        window.__pendingXHRRequests.push({
                            xhr: xhr,
                            requestData: JSON.parse(JSON.stringify(requestData))
                        });
                        
                        // Set up response interceptor
                        var originalOnReadyStateChange = xhr.onreadystatechange;
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                var responseData = {
                                    url: requestData.url,
                                    method: requestData.method,
                                    status: xhr.status,
                                    statusText: xhr.statusText,
                                    responseText: xhr.responseText,
                                    responseHeaders: xhr.getAllResponseHeaders(),
                                    timestamp: Date.now()
                                };
                                
                                // Store response for processing
                                window.__completedXHRRequests = window.__completedXHRRequests || [];
                                window.__completedXHRRequests.push(responseData);
                            }
                            
                            if (originalOnReadyStateChange) {
                                return originalOnReadyStateChange.apply(this, arguments);
                            }
                        };
                        
                        return originalSend.apply(this, arguments);
                    };
                    
                    return xhr;
                };
                
                // Override fetch API as well
                if (window.fetch) {
                    var originalFetch = window.fetch;
                    window.fetch = function(input, init) {
                        var url = typeof input === 'string' ? input : input.url;
                        var method = (init && init.method) || 'GET';
                        
                        var requestData = {
                            url: url,
                            method: method,
                            headers: (init && init.headers) || {},
                            body: (init && init.body) || null,
                            timestamp: Date.now()
                        };
                        
                        window.__pendingFetchRequests = window.__pendingFetchRequests || [];
                        window.__pendingFetchRequests.push(requestData);
                        
                        return originalFetch.apply(this, arguments).then(function(response) {
                            var responseData = {
                                url: url,
                                method: method,
                                status: response.status,
                                statusText: response.statusText,
                                headers: {},
                                timestamp: Date.now()
                            };
                            
                            // Extract headers
                            if (response.headers && response.headers.forEach) {
                                response.headers.forEach(function(value, key) {
                                    responseData.headers[key] = value;
                                });
                            }
                            
                            window.__completedFetchRequests = window.__completedFetchRequests || [];
                            window.__completedFetchRequests.push(responseData);
                            
                            return response;
                        });
                    };
                }
            })();
        ";
    }

    /**
     * Install the XHR interceptor in the browser
     */
    public function installXHRInterceptor(): void
    {
        if ($this->interceptorInstalled) {
            return;
        }

        try {
            $this->driver->executeScript($this->interceptorScript);
            $this->interceptorInstalled = true;
            echo "[INTERCEPTOR] XHR interceptor installed successfully\n";
        } catch (\Exception $e) {
            echo "[INTERCEPTOR] Failed to install XHR interceptor: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Process pending XHR requests
     */
    public function processPendingXHRRequests(): array
    {
        $pendingRequests = $this->driver->executeScript("
            var pending = window.__pendingXHRRequests || [];
            window.__pendingXHRRequests = [];
            return pending;
        ");

        $processedRequests = [];

        if (is_array($pendingRequests)) {
            foreach ($pendingRequests as $request) {
                if (isset($request['requestData'])) {
                    $requestData = $request['requestData'];

                    if ($this->xhrHandler->shouldIntercept($requestData['url'])) {
                        $modifiedRequest = $this->xhrHandler->beforeRequest($requestData);
                        $processedRequests[] = $modifiedRequest;
                    }
                }
            }
        }

        return $processedRequests;
    }

    /**
     * Process completed XHR responses
     */
    public function processCompletedXHRRequests(): array
    {
        $completedRequests = $this->driver->executeScript("
            var completed = window.__completedXHRRequests || [];
            window.__completedXHRRequests = [];
            return completed;
        ");

        $processedResponses = [];

        if (is_array($completedRequests)) {
            foreach ($completedRequests as $response) {
                if ($this->xhrHandler->shouldIntercept($response['url'])) {
                    $modifiedResponse = $this->xhrHandler->afterResponse($response);
                    $processedResponses[] = $modifiedResponse;
                }
            }
        }

        return $processedResponses;
    }

    /**
     * Process both fetch requests and responses
     */
    public function processFetchRequests(): array
    {
        $pendingFetch = $this->driver->executeScript("
            var pending = window.__pendingFetchRequests || [];
            window.__pendingFetchRequests = [];
            return pending;
        ");

        $completedFetch = $this->driver->executeScript("
            var completed = window.__completedFetchRequests || [];
            window.__completedFetchRequests = [];
            return completed;
        ");

        $result = ['requests' => [], 'responses' => []];

        if (is_array($pendingFetch)) {
            foreach ($pendingFetch as $request) {
                if ($this->xhrHandler->shouldIntercept($request['url'])) {
                    $result['requests'][] = $this->xhrHandler->beforeRequest($request);
                }
            }
        }

        if (is_array($completedFetch)) {
            foreach ($completedFetch as $response) {
                if ($this->xhrHandler->shouldIntercept($response['url'])) {
                    $result['responses'][] = $this->xhrHandler->afterResponse($response);
                }
            }
        }

        return $result;
    }

    /**
     * Wait for XHR requests to complete
     */
    public function waitForXHRCompletion(int $timeoutSeconds = 10): void
    {
        $wait = new WebDriverWait($this->driver, $timeoutSeconds);

        $wait->until(function () {
            $pendingCount = $this->driver->executeScript("
                return (window.__pendingXHRRequests || []).length + 
                       (window.__pendingFetchRequests || []).length;
            ");

            return $pendingCount == 0;
        });
    }

    /**
     * Get XHR statistics
     */
    public function getXHRStatistics(): array
    {
        return $this->driver->executeScript("
            return {
                pendingXHR: (window.__pendingXHRRequests || []).length,
                completedXHR: (window.__completedXHRRequests || []).length,
                pendingFetch: (window.__pendingFetchRequests || []).length,
                completedFetch: (window.__completedFetchRequests || []).length,
                interceptorInstalled: !!window.__xhrInterceptorInstalled
            };
        ");
    }

    /**
     * Set the XHR handler
     */
    public function setXHRHandler(XHRInterceptorInterface $handler): void
    {
        $this->xhrHandler = $handler;
    }

    /**
     * Get the current XHR handler
     */
    public function getXHRHandler(): XHRInterceptorInterface
    {
        return $this->xhrHandler;
    }
}
