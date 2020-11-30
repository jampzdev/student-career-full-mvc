<?php
/* Error reporting for windows server */
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
set_time_limit(0);
ini_set('memory_limit', -1);
error_reporting(1);

define('APP_PATH', realpath('..'));

try {

    /**
     * Read the configuration
     */
    $config = include APP_PATH . "/app/config/config.php";

    /**
     * Read auto-loader
     */
    include APP_PATH . "/app/config/loader.php";

    /**
     * Read services
     */
    include APP_PATH . "/app/config/services.php";

	/**
     * PHPMailer
     */
	include APP_PATH . "/app/library/mail/PHPMailerAutoload.php";

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
