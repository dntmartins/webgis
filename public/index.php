<?php
// phpinfo();
// exit();
// Define path to application directory
defined('PUBLIC_PATH') || define('PUBLIC_PATH', realpath(dirname(__FILE__)));
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
//Zend\Mvc\Application::init(require 'config/application.config.php')->run();

$application = Zend\Mvc\Application::init(require 'config/application.config.php');
//Insert the following line with correct basePath
$application->getRequest()->setBasePath($application->getConfig()['view_manager']['base_path']);
$application->run();
