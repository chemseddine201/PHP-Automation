<?php

namespace Services;

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverWait;

class AutomationService
{
    private array $handlers;
    private array $log = [];
    private RemoteWebDriver $driver;

    public function __construct(RemoteWebDriver $appDriver)
    {
        $this->driver = $appDriver;
        $this->registerHandlers();
    }

    /* -------------------------------------------------- */
    /* Public API                                         */
    /* -------------------------------------------------- */
    public function executeAction(ActionConfig $c): ActionResult
    {
        $t0 = microtime(true);
        try {
            $this->log("▶ {$c->action} on {$c->selector}");
            $msg = ($this->handlers[$c->action])($c);
            $t   = round((microtime(true) - $t0) * 1000, 2);
            $res = ActionResult::success((string)$msg, $t, $c->description);
            $this->log[] = $res;
            return $res;
        } catch (\Throwable $e) {
            $t   = round((microtime(true) - $t0) * 1000, 2);
            $res = ActionResult::error($e->getMessage(), $t, $c->description);
            $this->log[] = $res;
            if ($c->required) throw $e;
            return $res;
        }
    }

    public function executeActions(array $actions): WorkflowResult
    {
        $t0 = microtime(true);
        $results = [];
        foreach ($actions as $a) {
            $cfg = is_array($a) ? new ActionConfig($a) : $a;
            $results[] = $this->executeAction($cfg);
            if (!$results[array_key_last($results)]->isSuccess() && $cfg->required) break;
        }
        return new WorkflowResult($results, round((microtime(true) - $t0) * 1000, 2));
    }

    public function getExecutionLog(): array
    {
        return $this->log;
    }

    /* -------------------------------------------------- */
    private function registerHandlers(): void
    {
        $this->handlers = [
            'click'            => fn($c) => $this->performClick($c),
            'type'             => fn($c) => $this->performType($c),
            'clear'            => fn($c) => $this->performClear($c),
            'checkbox'         => fn($c) => $this->performCheckbox($c),
            'doubleClick'      => fn($c) => $this->performDoubleClick($c),
            'rightClick'       => fn($c) => $this->performRightClick($c),
            'hover'            => fn($c) => $this->performHover($c),
            'dragAndDrop'      => fn($c) => $this->performDragAndDrop($c),
            'select'           => fn($c) => $this->performSelect($c),
            'scroll'           => fn($c) => $this->performScroll($c),
            'scrollIntoView'   => fn($c) => $this->performScrollIntoView($c),
            'navigateTo'       => fn($c) => $this->performNavigateTo($c),
            'refresh'          => fn($c) => $this->performRefresh($c),
            'back'             => fn($c) => $this->performBack($c),
            'forward'          => fn($c) => $this->performForward($c),
            'wait'             => fn($c) => $this->performWait($c),
            'waitForElement'   => fn($c) => $this->performWaitForElement($c),
            'waitForVisible'   => fn($c) => $this->performWaitForVisible($c),
            'waitForClickable' => fn($c) => $this->performWaitForClickable($c),
            'waitForText'      => fn($c) => $this->performWaitForText($c),
            'executeScript'    => fn($c) => $this->performExecuteScript($c),
            'screenshot'       => fn($c) => $this->performScreenshot($c),
            'getText'          => fn($c) => $this->performGetText($c),
            'getAttribute'     => fn($c) => $this->performGetAttribute($c),
            'isDisplayed'      => fn($c) => $this->performIsDisplayed($c),
            'getTitle'         => fn($c) => $this->performGetTitle($c),
            'getCurrentUrl'    => fn($c) => $this->performGetCurrentUrl($c),
            'uploadFile'       => fn($c) => $this->performUploadFile($c),
            'sleep'            => fn($c) => $this->performSleep($c),
        ];
    }

    /* -------------------------------------------------- */
    /* PRIVATE IMPLEMENTATIONS – always return string     */
    /* -------------------------------------------------- */
    private function performClick(ActionConfig $c): string
    {
        $this->waitForClickable($c)->click();
        return 'Clicked';
    }

    private function performType(ActionConfig $c): string
    {
        $this->waitForVisible($c)->sendKeys($c->params['text'] ?? '');
        return 'Typed: ' . ($c->params['text'] ?? '');
    }

    private function performClear(ActionConfig $c): string
    {
        $this->waitForVisible($c)->clear();
        return 'Cleared';
    }

    private function performCheckbox(ActionConfig $c): string
    {
        $el = $this->waitForVisible($c);
        $check = $c->params['check'] ?? true;
        if ($check && !$el->isSelected()) $el->click();
        elseif (!$check && $el->isSelected()) $el->click();
        return 'Checkbox toggled';
    }

    private function performDoubleClick(ActionConfig $c): string
    {
        (new WebDriverActions($this->driver))->doubleClick($this->waitForVisible($c))->perform();
        return 'Double-clicked';
    }

    private function performRightClick(ActionConfig $c): string
    {
        (new WebDriverActions($this->driver))->contextClick($this->waitForVisible($c))->perform();
        return 'Right-clicked';
    }

    private function performHover(ActionConfig $c): string
    {
        (new WebDriverActions($this->driver))->moveToElement($this->waitForVisible($c))->perform();
        return 'Hovered';
    }

    private function performDragAndDrop(ActionConfig $c): string
    {
        $src = $this->waitForVisible($c);
        $tgt = $this->driver->findElement(
            $this->by($c->params['target'], $c->params['targetLocatorType'] ?? 'css')
        );
        (new WebDriverActions($this->driver))->dragAndDrop($src, $tgt)->perform();
        return 'Drag-and-dropped';
    }

    private function performSelect(ActionConfig $c): string
    {
        $sel = new WebDriverSelect($this->waitForVisible($c));
        if (isset($c->params['value'])) {
            $sel->selectByValue($c->params['value']);
            return 'Selected by value';
        }
        if (isset($c->params['text'])) {
            $sel->selectByVisibleText($c->params['text']);
            return 'Selected by text';
        }
        if (isset($c->params['index'])) {
            $sel->selectByIndex($c->params['index']);
            return 'Selected by index';
        }
        throw new \InvalidArgumentException('No selection criteria');
    }

    private function performScroll(ActionConfig $c): string
    {
        $this->driver->executeScript(
            'window.scrollBy(arguments[0], arguments[1]);',
            [$c->params['x'] ?? 0, $c->params['y'] ?? 0]
        );
        return 'Scrolled';
    }

    private function performScrollIntoView(ActionConfig $c): string
    {
        $this->driver->executeScript(
            'arguments[0].scrollIntoView({behavior:"smooth",block:"center"});',
            [$this->waitForVisible($c)]
        );
        return 'Scrolled into view';
    }

    private function performNavigateTo(ActionConfig $c): string
    {
        $this->driver->get($c->params['url'] ?? '');
        return 'Navigated';
    }

    private function performRefresh(ActionConfig $c): string
    {
        $this->driver->navigate()->refresh();
        return 'Refreshed';
    }

    private function performBack(ActionConfig $c): string
    {
        $this->driver->navigate()->back();
        return 'Back';
    }

    private function performForward(ActionConfig $c): string
    {
        $this->driver->navigate()->forward();
        return 'Forward';
    }

    private function performWait(ActionConfig $c): string
    {
        sleep($c->params['seconds'] ?? 1);
        return 'Waited';
    }

    /* -------------------------------------------------- */
    /* Internal element getters – used only by helpers    */
    /* -------------------------------------------------- */
    private function waitForElement(ActionConfig $c): WebDriverElement
    {
        return $this->wait($c)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                $this->by($c->selector, $c->locatorType)
            )
        );
    }

    private function waitForVisible(ActionConfig $c): WebDriverElement
    {
        return $this->wait($c)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                $this->by($c->selector, $c->locatorType)
            )
        );
    }

    private function waitForClickable(ActionConfig $c): WebDriverElement
    {
        return $this->wait($c)->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $this->by($c->selector, $c->locatorType)
            )
        );
    }

    /* -------------------------------------------------- */
    /* Remaining helpers  */
    private function performWaitForText(ActionConfig $c): string
    {
        $this->wait($c)->until(
            WebDriverExpectedCondition::textToBePresentInElement(
                $this->by($c->selector, $c->locatorType),
                $c->params['text']
            )
        );
        return 'Text present';
    }

    private function performExecuteScript(ActionConfig $c): string
    {
        $r = $this->driver->executeScript($c->params['script'], $c->params['args'] ?? []);
        return 'Script executed: ' . json_encode($r);
    }

    private function performScreenshot(ActionConfig $c): string
    {
        $f = $c->params['filename'] ?? 'shot_' . date('Y-m-d_H-i-s') . '.png';
        $this->driver->takeScreenshot($f);
        return "Screenshot $f";
    }

    private function performGetText(ActionConfig $c): string
    {
        return $this->waitForVisible($c)->getText();
    }

    private function performGetAttribute(ActionConfig $c): string
    {
        return $this->waitForVisible($c)->getAttribute($c->params['attribute']);
    }

    private function performIsDisplayed(ActionConfig $c): string
    {
        return $this->waitForVisible($c)->isDisplayed() ? 'true' : 'false';
    }

    private function performGetTitle(ActionConfig $c): string
    {
        return $this->driver->getTitle();
    }

    private function performGetCurrentUrl(ActionConfig $c): string
    {
        return $this->driver->getCurrentURL();
    }

    private function performUploadFile(ActionConfig $c): string
    {
        $this->waitForVisible($c)->sendKeys($c->params['filePath']);
        return 'File uploaded';
    }

    private function performSleep(ActionConfig $c): string
    {
        usleep(($c->params['milliseconds'] ?? 1000) * 1000);
        return 'Slept';
    }

    private function wait(ActionConfig $c): WebDriverWait
    {
        return new WebDriverWait($this->driver, $c->timeout, $c->interval);
    }

    private function by(string $selector, string $type = 'css'): WebDriverBy
    {
        return match ($type) {
            'id'    => WebDriverBy::id($selector),
            'css'   => WebDriverBy::cssSelector($selector),
            'xpath' => WebDriverBy::xpath($selector),
            'class' => WebDriverBy::className($selector),
            'name'  => WebDriverBy::name($selector),
            'tag'   => WebDriverBy::tagName($selector),
            default => WebDriverBy::cssSelector($selector),
        };
    }

    private function log(string $msg): void
    {
        AsyncLogger::get()->info($msg);
    }

    private function performWaitForElement(ActionConfig $c): string
    {
        $this->wait($c)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                $this->by($c->selector, $c->locatorType)
            )
        );
        return 'Element present';
    }

    private function performWaitForVisible(ActionConfig $c): string
    {
        $this->wait($c)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                $this->by($c->selector, $c->locatorType)
            )
        );
        return 'Element visible';
    }

    private function performWaitForClickable(ActionConfig $c): string
    {
        $this->wait($c)->until(
            WebDriverExpectedCondition::elementToBeClickable(
                $this->by($c->selector, $c->locatorType)
            )
        );
        return 'Element clickable';
    }
}
