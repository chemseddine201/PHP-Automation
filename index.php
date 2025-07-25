<?php
require_once __DIR__ . '/autoload.php';

use Dotenv\Dotenv;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Services\AutomationService;
use Services\JobExecutor;

use Facebook\WebDriver\Remote\{RemoteWebDriver, DesiredCapabilities};
use Pages\{
    CompanyLoginPage,
    ProjectFormPage,
    ProjectViewPage,
    UserLoginPage
};
use Services\Interception\CustomXHRHandler;
use Services\Interception\DefaultXHRHandler;
use Services\Interception\InterceptingWebDriverFactory;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['HOST_URL'];
$url = $_ENV['MAIN_URL'];
$parallel = $_ENV['PARALLEL'] === 'true';


$capabilities = DesiredCapabilities::chrome();
$chromeOptions = new ChromeOptions();
$chromeOptions->addArguments(['--no-sandbox', '--disable-dev-shm-usage']); //'--headless','--no-sandbox', '--disable-dev-shm-usage'
$capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

//TODO: play with interceptors to break requests
$customHandler = (new CustomXHRHandler)
    ->addBreaker(
        fn(array $req) =>
        $req['method'] === 'POST' &&
            strpos($req['url'], '/api/company/login') !== false
    );

// Add modification rules to default handler
$defaultHandler = new DefaultXHRHandler();
$defaultHandler->addModificationRule(
    'API Header Injection',
    '*/api/*',
    'before',
    ['headers' => []] //'Authorization' => 'Bearer test-token'
);

$driver = InterceptingWebDriverFactory::create(
    'http://localhost:4444/wd/hub',
    DesiredCapabilities::chrome(),
    $customHandler,
    true
);


$jobs = [
    'companyLogin' => fn(AutomationService $as) => (new CompanyLoginPage($as))->loginCompanyId('11111'),
    //'userLogin' => fn(AutomationService $as) => (new UserLoginPage($as))->loginEmailPassword("mohamed.abodaif18@gmail.com", "Abodaif@2018"),
    //'viewProject' => fn(AutomationService $as) => (new ProjectViewPage($as))->viewFirstProject(),
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
$xhrRequests = $driver->processPendingXHRRequests();
$xhrResponses = $driver->processCompletedXHRRequests();
$fetchData = $driver->processFetchRequests();

print_r($xhrRequests);
print(PHP_EOL . "==========================" . PHP_EOL);
print_r($xhrResponses);
print(PHP_EOL . "==========================" . PHP_EOL);

echo "Processed " . count($xhrRequests) . " XHR requests\n";
echo "Processed " . count($xhrResponses) . " XHR responses\n";
echo "Processed " . count($fetchData['requests']) . " fetch requests\n";
echo "Processed " . count($fetchData['responses']) . " fetch responses\n";

// Get statistics
//$stats = $driver->getXHRStatistics();
//print_r($stats);
/*         
foreach ($results as $label => $wf) {
    //print_r($wf?->getResults());
    //echo "$label : OK={}  ERR={$wf->getErrorCount()}\n";
} */

$driver->quit();
