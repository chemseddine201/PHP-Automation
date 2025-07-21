<?php

namespace Services;

class WorkflowBuilder
{
    private array $actions = [];

    public function click(string $sel, array $opts = []): self
    {
        return $this->add('click', $sel, $opts);
    }

    public function type(string $sel, string $text, array $opts = []): self
    {
        return $this->add('type', $sel, array_merge($opts, ['text' => $text]));
    }

    public function clear(string $sel, array $opts = []): self
    {
        return $this->add('clear', $sel, $opts);
    }

    public function checkbox(string $sel, bool $check = true, array $opts = []): self
    {
        return $this->add('checkbox', $sel, array_merge($opts, ['check' => $check]));
    }

    public function doubleClick(string $sel, array $opts = []): self
    {
        return $this->add('doubleClick', $sel, $opts);
    }

    public function rightClick(string $sel, array $opts = []): self
    {
        return $this->add('rightClick', $sel, $opts);
    }

    public function hover(string $sel, array $opts = []): self
    {
        return $this->add('hover', $sel, $opts);
    }

    public function select(string $sel, array $params, array $opts = []): self
    {
        return $this->add('select', $sel, array_merge($opts, $params));
    }

    public function scroll(int $x = 0, int $y = 0): self
    {
        return $this->add('scroll', '', ['x' => $x, 'y' => $y]);
    }

    public function scrollIntoView(string $sel, array $opts = []): self
    {
        return $this->add('scrollIntoView', $sel, $opts);
    }

    public function navigateTo(string $url): self
    {
        return $this->add('navigateTo', '', ['url' => $url]);
    }

    public function refresh(): self
    {
        return $this->add('refresh', '');
    }

    public function back(): self
    {
        return $this->add('back', '');
    }

    public function forward(): self
    {
        return $this->add('forward', '');
    }

    public function wait(int $s): self
    {
        return $this->add('wait', '', ['seconds' => $s]);
    }

    public function waitForElement(string $sel, array $opts = []): self
    {
        return $this->add('waitForElement', $sel, $opts);
    }

    public function waitForVisible(string $sel, array $opts = []): self
    {
        return $this->add('waitForVisible', $sel, $opts);
    }

    public function waitForClickable(string $sel, array $opts = []): self
    {
        return $this->add('waitForClickable', $sel, $opts);
    }

    public function waitForText(string $sel, string $text, array $opts = []): self
    {
        return $this->add('waitForText', $sel, array_merge($opts, ['text' => $text]));
    }

    public function executeScript(string $js, array $args = [], array $opts = []): self
    {
        return $this->add('executeScript', '', array_merge($opts, ['script' => $js, 'args' => $args]));
    }

    public function screenshot(?string $file = null): self
    {
        return $this->add('screenshot', '', $file ? ['filename' => $file] : []);
    }

    public function uploadFile(string $sel, string $path, array $opts = []): self
    {
        return $this->add('uploadFile', $sel, array_merge($opts, ['filePath' => $path]));
    }

    private function add(string $a, string $s, array $p = []): self
    {
        //TODO:;
        $this->actions[] = array_merge(['action' => $a, 'selector' => $s, 'params' => $p]);
        return $this;
    }

    public function getText(string $selector, array $opts = []): self
    {
        return $this->add('getText', $selector, $opts);
    }

    public function sleep(int $milliseconds = 1000): self
    {
        return $this->add('sleep', '', ['milliseconds' => $milliseconds]);
    }

    public function build(): array
    {
        //print_r($this->actions);//

        $result = array_map(fn($a) => new ActionConfig($a), $this->actions);
        //print_r($result);
        //die;
        return $result;
    }

    public function execute(AutomationService $svc): WorkflowResult
    {
        return $svc->executeActions($this->build());
    }
}
