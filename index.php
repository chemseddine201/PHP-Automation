<?php
require_once __DIR__ . '/autoload.php';

use Dotenv\Dotenv;
use Services\AutomationService;
use Services\JobExecutor;

use Facebook\WebDriver\Remote\{RemoteWebDriver, DesiredCapabilities};
use Pages\{
    CompanyLoginPage,
    ProjectFormPage,
    ProjectViewPage,
    UserLoginPage
};

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['HOST_URL'];
$url = $_ENV['MAIN_URL'];
$parallel = $_ENV['PARALLEL'] === 'true';

$driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome(), 2 * 60000, 2 * 60000);
//

$jobs = [
    'companyLogin' => fn(AutomationService $as) => (new CompanyLoginPage($as))->loginCompanyId('11111'),
    'userLogin' => fn(AutomationService $as) => (new UserLoginPage($as))->loginEmailPassword("mohamed.abodaif18@gmail.com", "Abodaif@2018"),
    'viewProject' => fn(AutomationService $as) => (new ProjectViewPage($as))->viewFirstProject(),
    //'viewProject' => (new ProjectFormPage($automation))->createProject([
    //    'name'        => 'Auto-Project',
    //    'description' => 'Created via workflow',
    //    'startDate'   => '2025-07-21',
    //    'environments' => ['Development', 'Staging'],
    //    'pages'       => ['Home', 'About', 'Contact'],
    //]), 
    /* 'parallelExample' => [
        ['action' => 'navigateTo', 'params' => ['url' => "https://stg.bugs-tracker.com/projects"]],
        ['action' => 'type', 'selector' => '#name', 'params' => ['text' => 'Auto-Project']],
        ['action' => 'click', 'selector' => 'button[type="submit"]'],
    ] */
];

$results = JobExecutor::run($driver, $jobs, false);

foreach ($results as $label => $wf) {
    print_r($wf?->getResults());
    //echo "$label : OK={}  ERR={$wf->getErrorCount()}\n";
}
