<?php

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
