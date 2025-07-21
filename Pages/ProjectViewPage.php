<?php

namespace Pages;

use Services\{BasePage, WorkflowBuilder, WorkflowResult};

/**
 * Upgraded Project-View page object
 * Compatible with UniversalWebAutomationService
 */
class ProjectViewPage extends BasePage
{
    protected function initializeElements(): void
    {
        $this->defineElement('firstRow',           'table tbody tr');
        $this->defineElement('rowMenuBtn',         'table tbody tr:first-child .column-action .btn');
        $this->defineElement('rowViewBtn',         'table tbody tr:first-child .dropdown-menu button[role="menuitem"]');
        $this->defineElement('modalBody',          '.modal-body');
        $this->defineElement('modalCloseBtn',      '.modal-footer .btn-close, .modal-header .close');
    }

    /**
     * Opens the details modal for the first project in the table.
     * Returns a WorkflowResult object for inspection.
     */
    public function viewFirstProject(): WorkflowResult
    {
        return (new WorkflowBuilder())
            ->navigateTo("https://stg.bugs-tracker.com/projects")
            // 1. Ensure table is populated
            ->waitForElement($this->getElement('firstRow')['selector'], ['description' => 'Wait for table rows'])

            // 2. Open the three-dots menu
            ->click($this->getElement('rowMenuBtn')['selector'], ['description' => 'Open row menu'])

            // 3. Wait until the dropdown is visible
            ->waitForVisible($this->getElement('rowViewBtn')['selector'], ['description' => 'Wait for View button'])

            // 4. Click "View"
            ->click($this->getElement('rowViewBtn')['selector'], ['description' => 'Click View'])

            // 5. Wait for the modal to appear and fetch its text
            ->waitForVisible($this->getElement('modalBody')['selector'], ['description' => 'Wait for modal'])
            ->getText($this->getElement('modalBody')['selector'], ['description' => 'Capture modal text'])

            ->execute($this->automation);
    }

    /**
     * Convenience helper to close the modal (if it is still open).
     */
    public function closeModal(): WorkflowResult
    {
        return (new WorkflowBuilder())
            ->click($this->getElement('modalCloseBtn')['selector'], ['required' => false, 'description' => 'Close modal'])
            ->execute($this->automation);
    }
}
