<?php

namespace Services\Parallel;

use Amp\Parallel\Worker\Task;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

final class DriverTask implements Task
{
    public function __construct(
        private string $host,
        private DesiredCapabilities $capabilities
    ) {}

    public function run(Channel $channel, Cancellation $cancellation): RemoteWebDriver
    {
        return RemoteWebDriver::create($this->host, $this->capabilities);
    }
}
