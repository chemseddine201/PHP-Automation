<?php


use Services\{BasePage, WorkflowBuilder, WorkflowResult};

/**
 * Company-ID login page
 * Compatible with UniversalWebAutomationService
 */
class CompanyLoginPage extends BasePage
{
    protected function initializeElements(): void
    {
        $this->defineElement('companyIdInput', '#company_id');
        $this->defineElement('submitBtn',      'button[type="submit"]');
        $this->defineElement('nextScreen',     'input#login-email'); // adjust selector for your real next screen
    }

    /**
     * Enter company ID and proceed to user login
     */
    public function loginCompanyId(string $companyId): WorkflowResult
    {
        return (new WorkflowBuilder())
            ->navigateTo("https://stg.bugs-tracker.com/company-login")
            ->waitForVisible($this->getElement('companyIdInput')['selector'], ['locatorType' => 'id', 'description' => 'Wait for company-id field'])
            ->type($this->getElement('companyIdInput')['selector'], $companyId, ['locatorType' => 'id', 'description' => 'Enter company ID'])
            ->click($this->getElement('submitBtn')['selector'], ['description' => 'Submit company ID'])
            ->waitForVisible($this->getElement('nextScreen')['selector'], ['description' => 'Land on next login screen'])
            ->execute($this->automation);
    }
}