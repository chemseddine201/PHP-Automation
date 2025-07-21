<?php

namespace Pages;

use Services\{BasePage, WorkflowBuilder, WorkflowResult};

/**
 * User credentials login page – upgraded to the framework
 */
class UserLoginPage extends BasePage
{
    protected function initializeElements(): void
    {
        $this->defineElement('emailInput',    'input#login-email');
        $this->defineElement('passwordInput', 'input#password');
        $this->defineElement('submitBtn',     'button[type="submit"]');
        $this->defineElement('dashboard',     '.dashboard, .main-content, [data-testid="dashboard"]');
    }

    /**
     * Enter e-mail / password and land on the dashboard
     */
    public function loginEmailPassword(string $email, string $password): WorkflowResult
    {
        return (new WorkflowBuilder())
            //->navigateTo("https://stg.bugs-tracker.com/users/login")
            ->waitForVisible($this->getElement('emailInput')['selector'], ['locatorType' => 'id', 'description' => 'Wait for e-mail field'])
            ->type($this->getElement('emailInput')['selector'], $email, ['locatorType' => 'id', 'description' => 'Enter e-mail'])
            ->type($this->getElement('passwordInput')['selector'], $password, ['locatorType' => 'id', 'description' => 'Enter password'])
            ->click($this->getElement('submitBtn')['selector'], ['description' => 'Submit credentials'])
            ->waitForVisible($this->getElement('dashboard')['selector'], ['description' => 'Land on dashboard'])
            ->execute($this->automation);
    }
}
