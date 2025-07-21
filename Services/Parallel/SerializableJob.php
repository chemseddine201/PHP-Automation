<?php

namespace Services\Parallel;

use Amp\Parallel\Worker\Task;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Services\AutomationService;

final class SerializableJob implements Task
{
    public function __construct(private array $actions) {}

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $host = $_ENV['SELENIUM_HUB_URL'] ?? 'http://localhost:4444/wd/hub';
        $capabilities = DesiredCapabilities::chrome();

        $driver = RemoteWebDriver::create($host, $capabilities);
        $service = new AutomationService($driver);

        try {
            return $service->executeActions($this->actions);
        } finally {
            $driver->quit();
        }
    }
}
