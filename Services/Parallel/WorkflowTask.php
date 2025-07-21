<?php

namespace Services\Parallel;

use Amp\Parallel\Worker\Task;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Services\AutomationService;

final class WorkflowTask implements Task
{
    private string $hub = 'http://localhost:4444/wd/hub';
    public function __construct(private $callable) {}

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $host = $_ENV['SELENIUM_HUB_URL'] ?? $this->hub;
        $capabilities = DesiredCapabilities::chrome();

        $driver = RemoteWebDriver::create($host, $capabilities);
        $service = new AutomationService($driver);

        try {
            $result = ($this->callable)($service);
            return $result;
        } finally {
            $driver->quit();
        }
    }
}
