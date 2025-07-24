<?php

namespace Services\Interception;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverWait;

/**
 * Enhanced Remote WebDriver with XHR Interception
 * Extends Facebook\WebDriver\Remote\RemoteWebDriver
 */
class InterceptingRemoteWebDriver extends RemoteWebDriver
{
    private XHRInterceptorWrapper $wrapper;
    private bool $autoInstall = false;

    public function __construct(
        $executor,
        $sessionId,
        $desiredCapabilities,
        XHRInterceptorInterface $xhrHandler = null,
        bool $autoInstall = false
    ) {
        parent::__construct($executor, $sessionId, $desiredCapabilities);
        $this->wrapper = new XHRInterceptorWrapper($this, $xhrHandler);
        $this->autoInstall = $autoInstall;
    }

    /**
     * Override get method to auto-install interceptor
     */
    public function get($url): void
    {
        parent::get($url);
        if ($this->autoInstall) {
            usleep(100000);
            $this->wrapper->installXHRInterceptor();
        }
    }

    /**
     * Delegate XHR methods to wrapper
     */
    public function processPendingXHRRequests(): array
    {
        return $this->wrapper->processPendingXHRRequests();
    }

    public function processCompletedXHRRequests(): array
    {
        return $this->wrapper->processCompletedXHRRequests();
    }

    public function processFetchRequests(): array
    {
        return $this->wrapper->processFetchRequests();
    }

    public function waitForXHRCompletion(int $timeoutSeconds = 10): void
    {
        $this->wrapper->waitForXHRCompletion($timeoutSeconds);
    }

    public function getXHRStatistics(): array
    {
        return $this->wrapper->getXHRStatistics();
    }

    public function setXHRHandler(XHRInterceptorInterface $handler): void
    {
        $this->wrapper->setXHRHandler($handler);
    }

    public function getXHRHandler(): XHRInterceptorInterface
    {
        return $this->wrapper->getXHRHandler();
    }

    public function execute($command_name, $params = [])
    {
        if ($command_name === 'sendKeysToElement' && isset($params['value']) && is_array($params['value'])) {
            $params['text'] = (string) $params['value'][0];   // scalar string
            var_dump($params);
        }

        return parent::execute($command_name, $params);
    }
}
