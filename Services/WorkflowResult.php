<?php

namespace Services;

class WorkflowResult
{
    public function __construct(private array $results, private float $totalTime) {}

    public function getResults(): array
    {
        return $this->results;
    }
    public function getTotalExecutionTime(): float
    {
        return $this->totalTime;
    }
    public function getSuccessCount(): int
    {
        return count(array_filter($this->results, fn($r) => $r->isSuccess()));
    }
    public function getErrorCount(): int
    {
        return count($this->results) - $this->getSuccessCount();
    }
    public function isFullySuccessful(): bool
    {
        return $this->getErrorCount() === 0;
    }
    public function toArray(): array
    {
        return [
            'results' => array_map(fn($r) => $r->toArray(), $this->results),
            'totalTime' => $this->totalTime,
            'successCount' => $this->getSuccessCount(),
            'errorCount'   => $this->getErrorCount(),
            'fullySuccessful' => $this->isFullySuccessful(),
        ];
    }
}
