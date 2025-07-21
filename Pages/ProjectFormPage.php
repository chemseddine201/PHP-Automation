<?php

namespace Pages;

use Services\{BasePage, WorkflowBuilder, WorkflowResult};

/**
 * Upgraded Project-Create form page
 * Uses the UniversalWebAutomationService workflow engine
 */
class ProjectFormPage extends BasePage
{
    protected function initializeElements(): void
    {
        $this->defineElement('createBtn',     'button.btn-success');
        $this->defineElement('nameInput',     '#project_name');
        $this->defineElement('descInput',     '#description');
        $this->defineElement('startInput',    '#start_date');
        $this->defineElement('envDropdown',   'div.select__control');
        $this->defineElement('envInput',      'input.select__input');
        $this->defineElement('submitBtn',     'button[type="submit"]');
        $this->defineElement('successNote',   '.alert-success, .toast-success');
        $this->defineElement('team0',         '#teams_types-0');
        $this->defineElement('team1',         '#teams_types-1');
        $this->defineElement('team2',         '#teams_types-2');
        $this->defineElement('addPageBtn',    '#tbody-pixel > tr:last-child .table-repeater-actions svg.text-success');
    }

    /**
     * Creates a project in one call.
     *
     * @param array{
     *   name: string,
     *   description: string,
     *   startDate: string,
     *   environments?: string[],
     *   pages?: string[]
     * } $data
     */
    public function createProject(array $data): WorkflowResult
    {
        $w = (new WorkflowBuilder())
            ->navigateTo("https://stg.bugs-tracker.com/projects")
            ->click($this->getElement('createBtn')['selector'], ['description' => 'Click create button'])
            ->type($this->getElement('nameInput')['selector'], $data['name'], ['locatorType' => 'id', 'description' => 'Enter project name'])
            ->type($this->getElement('descInput')['selector'], $data['description'], ['locatorType' => 'id', 'description' => 'Enter description'])
            ->executeScript("arguments[0].removeAttribute('readonly');", ['args' => []], ['description' => 'Remove readonly on start_date'])
            ->type($this->getElement('startInput')['selector'], $data['startDate'], ['locatorType' => 'id', 'clear' => true, 'description' => 'Set start date']);

        // Choose environments
        if (!empty($data['environments'])) {
            $w->click($this->getElement('envDropdown')['selector'], ['description' => 'Open env dropdown']);
            foreach ($data['environments'] as $env) {
                $w->type($this->getElement('envInput')['selector'], $env, ['clear' => false])
                    ->click('//div[contains(@class,"select__option") and contains(text(),"' . $env . '")]', ['locatorType' => 'xpath'])
                    ->sleep(500);
            }
        }

        // Select all team-type checkboxes
        foreach (['team0', 'team1', 'team2'] as $team) {
            $w->checkbox($this->getElement($team)['selector'], true, ['locatorType' => 'id']);
        }

        // Add pages
        if (!empty($data['pages'])) {
            foreach ($data['pages'] as $i => $pageName) {
                $fieldId = "pages.$i.page_name";
                $w->type($fieldId, $pageName, ['locatorType' => 'id']);
                if ($i < count($data['pages']) - 1) {
                    $w->click($this->getElement('addPageBtn')['selector'])
                        ->waitForElement("pages." . ($i + 1) . ".page_name", ['locatorType' => 'id']);
                }
            }
        }

        return $w->click($this->getElement('submitBtn')['selector'], ['description' => 'Submit form'])
            ->waitForVisible($this->getElement('successNote')['selector'], ['timeout' => 10, 'description' => 'Wait success notification'])
            ->execute($this->automation);
    }
}
