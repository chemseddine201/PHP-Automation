<?php

namespace Services\Parallel;

use Amp\Parallel\Worker;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

final class DriverPool
{
    private Worker\WorkerPool $pool;

    public function __construct(
        private string $host,
        private DesiredCapabilities $capabilities,
        private int $size
    ) {
        $this->pool = Worker\workerPool();
    }

    /** @return Worker\Execution<RemoteWebDriver> */
    public function submit(): Worker\Execution
    {
        return $this->pool->submit(new DriverTask($this->host, $this->capabilities));
    }
}
