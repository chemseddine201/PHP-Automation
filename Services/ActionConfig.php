<?php

namespace Services;

class ActionConfig
{
    public string  $action;
    public string  $selector;
    public string  $locatorType = 'css';
    public array   $params      = [];
    public int     $timeout     = 15;
    public int     $interval    = 500;
    public bool    $required    = true;
    public ?string $description = null;

    public function __construct(array $config)
    {
        foreach ($config as $k => $v) {
            if (property_exists($this, $k)) $this->$k = $v;
        }
    }

    public static function create(string $action, string $selector, array $cfg = []): self
    {
        return new self(array_merge(['action' => $action, 'selector' => $selector], $cfg));
    }
}
