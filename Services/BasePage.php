<?php

namespace Services;

abstract class BasePage
{
    protected AutomationService $automation;
    protected array $pageElements = [];

    public function __construct(AutomationService $automation)
    {
        $this->automation = $automation;
        $this->initializeElements();
    }

    abstract protected function initializeElements(): void;

    protected function defineElement(string $name, string $selector, string $locatorType = 'css'): void
    {
        $this->pageElements[$name] = ['selector' => $selector, 'locatorType' => $locatorType];
    }

    protected function getElement(string $name): array
    {
        if (!isset($this->pageElements[$name])) {
            throw new \InvalidArgumentException("Element '$name' not defined");
        }
        return $this->pageElements[$name];
    }

    protected function createAction(string $action, string $elementName, array $params = [], array $options = []): ActionConfig
    {
        $element = $this->getElement($elementName);
        return ActionConfig::create($action, $element['selector'], array_merge([
            'locatorType' => $element['locatorType'],
            'params' => $params
        ], $options));
    }

    public function executePageAction(string $action, string $elementName, array $params = [], array $options = []): ActionResult
    {
        $actionConfig = $this->createAction($action, $elementName, $params, $options);
        return $this->automation->executeAction($actionConfig);
    }
}
