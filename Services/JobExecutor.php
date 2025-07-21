<?php

namespace Services;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Services\Parallel\SerializableJob;
use Services\Parallel\CustomCallableJob;

final class JobExecutor
{
    /**
     * @param RemoteWebDriver                               $driver   Used only in sync mode
     * @param array<string, callable|array<ActionConfig|array>> $jobs
     * @param bool                                          $parallel
     * @return array<string, WorkflowResult>
     */
    public static function run(
        RemoteWebDriver $driver,
        array $jobs,
        bool $parallel = false
    ): array {
        $normalized = [];

        foreach ($jobs as $label => $job) {
            $isArrayJob = is_array($job);

            if ($parallel && !$isArrayJob) {
                throw new \RuntimeException("Only array-based action jobs can be executed in parallel. Closure found at [$label].");
            }

            $normalized[$label] = $isArrayJob
                ? array_map(fn($a) => $a instanceof ActionConfig ? $a : new ActionConfig($a), $job)
                : $job; // Callable for sync
        }

        return $parallel
            ? self::runParallel($normalized)
            : self::runSequential($driver, $normalized);
    }


    private static function runSequential(RemoteWebDriver $driver, array $jobs): array
    {
        $service = new AutomationService($driver);
        $results = [];

        foreach ($jobs as $label => $job) {
            $results[$label] = is_callable($job)
                ? $job($service)
                : $service->executeActions($job);
        }

        return $results;
    }

    private static function runParallel(array $actionsByLabel): array
    {
        $pool = \Amp\Parallel\Worker\workerPool();
        $executions = [];

        foreach ($actionsByLabel as $label => $actions) {
            $executions[$label] = $pool->submit(new SerializableJob($actions));
        }

        $results = [];
        foreach ($executions as $label => $execution) {
            $results[$label] = $execution->await();
        }

        return $results;
    }
}
