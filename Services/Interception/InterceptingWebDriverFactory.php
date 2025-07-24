<?php

namespace  Services\Interception;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\HttpCommandExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCommand;

/**
 * Factory class for creating intercepting web drivers
 */ class InterceptingWebDriverFactory
{
    /**
     * Create intercepting driver by extending a standard RemoteWebDriver
     */
    public static function create(
        string $host = 'http://localhost:4444/wd/hub',
        DesiredCapabilities $capabilities = null,
        XHRInterceptorInterface $xhrHandler = null,
        $autoInstall = false
    ): InterceptingRemoteWebDriver {

        $capabilities = $capabilities ?: DesiredCapabilities::chrome();

        // Enable logging for network events
        $chromeOptions = [
            '--enable-logging',
            '--log-level=0',
            '--enable-network-service-logging'
        ];

        $capabilities->setCapability('goog:chromeOptions', [
            'args' => $chromeOptions
        ]);

        // Create executor
        $executor = new HttpCommandExecutor($host);

        // Start a new session using the low-level command approach
        // Filter out legacy capabilities that aren't W3C compliant
        $capabilitiesArray = $capabilities->toArray();
        $w3cCapabilities = [];

        // Map legacy capabilities to W3C format
        foreach ($capabilitiesArray as $key => $value) {
            // Skip legacy keys that cause W3C validation errors
            if (in_array($key, ['platform', 'version', 'javascriptEnabled'])) {
                continue;
            }
            $w3cCapabilities[$key] = $value;
        }

        $command = new WebDriverCommand(
            null,
            DriverCommand::NEW_SESSION,
            ['capabilities' => ['alwaysMatch' => $w3cCapabilities]]
        );

        $response = $executor->execute($command);
        $sessionId = $response->getSessionID();

        // Create our custom driver with the session
        return new InterceptingRemoteWebDriver($executor, $sessionId, $capabilities, $xhrHandler, $autoInstall);
    }

    /**
     * Create intercepting driver from existing RemoteWebDriver instance
     */
    public static function createFromExisting(
        RemoteWebDriver $existingDriver,
        XHRInterceptorInterface $xhrHandler = null,
        $autoInstall = false
    ): InterceptingRemoteWebDriver {

        $sessionId = $existingDriver->getSessionID();
        $executor = $existingDriver->getCommandExecutor();
        $capabilities = $existingDriver->getCapabilities();

        return new InterceptingRemoteWebDriver($executor, $sessionId, $capabilities, $xhrHandler, $autoInstall);
    }

    /**
     * Simple factory method that creates a fresh session
     */
    public static function createSimple(
        string $host = 'http://localhost:4444/wd/hub',
        DesiredCapabilities $capabilities = null,
        XHRInterceptorInterface $xhrHandler = null,
        $autoInstall = false
    ): InterceptingRemoteWebDriver {

        return static::create($host, $capabilities, $xhrHandler, $autoInstall);
    }
}
