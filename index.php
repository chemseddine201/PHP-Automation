<?php
// Simple autoloader for your project structure
spl_autoload_register(function ($class) {
    // Try exact namespace mapping first (Services\AutomationService -> Services/AutomationService.php)
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // Try just the class name in common directories
    $className = basename(str_replace('\\', DIRECTORY_SEPARATOR, $class));
    $directories = ['Services', 'Pages', 'Models', 'Utils', '.'];

    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            // Try direct file in directory
            $filePath = $dir . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }

            // Search recursively in subdirectories
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $fileInfo) {
                if (
                    $fileInfo->getExtension() === 'php' &&
                    $fileInfo->getBasename('.php') === $className
                ) {
                    require_once $fileInfo->getRealPath();
                    return;
                }
            }
        }
    }
});

use Services\AutomationService;
use Facebook\WebDriver\Remote\{RemoteWebDriver, DesiredCapabilities};
use Services\JobExecutor;

require 'vendor/autoload.php';

$host = 'http://localhost:4444';
$url = 'https://stg.bugs-tracker.com/';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$hubUrl   = $_ENV['SELENIUM_HUB_URL'];
$parallel = $_ENV['PARALLEL'] === 'true';

$driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome(), 60000, 60000);
//

$jobs = [
    'companyLogin' => fn(AutomationService $as) => (new CompanyLoginPage($as))->loginCompanyId('11111'),
    'userLogin' => fn(AutomationService $as) => (new UserLoginPage($as))->loginEmailPassword("mohamed.abodaif18@gmail.com", "Abodaif@2018"),
    'createProject' => fn(AutomationService $as) => (new ProjectViewPage($as))->viewFirstProject(),
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
