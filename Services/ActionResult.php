<?php

namespace Services;

class ActionResult
{
    private function __construct(
        private bool    $success,
        private string  $message,
        private float   $executionTime,
        private ?string $description = null,
        private array   $metadata    = []
    ) {}

    public static function success(string $msg, float $t, ?string $d = null, array $m = []): self
    {
        return new self(true,  $msg, $t, $d, $m);
    }

    public static function error(string $msg, float $t, ?string $d = null, array $m = []): self
    {
        return new self(false, $msg, $t, $d, $m);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
