<?php

class GF_BirdCRM_LogService
{
    private const IS_DEBUG_ENABLED = true;

	private static $_instance = null;

    public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

    protected function __construct() {
    }

    public function log($section, $message)
    {
        if (!self::IS_DEBUG_ENABLED) {
            return;
        }

        $logEntry = sprintf("[%s] (%s) %s\n", date('H:i:s T'), $section, $message);

        file_put_contents($this->getFilepath(), $logEntry, FILE_APPEND);
    }

    public function getFilepath()
    {
        $uploadDir = wp_upload_dir();
        
        $logFilePath = $uploadDir['basedir'] . '/gf_birdcrm_logs';

        // Log dir doesn't exist, create it
        if ( !is_dir($logFilePath) )
        {
            // Create dir
            mkdir($logFilePath, 0777, true);

            // Create a blank .htaccess file to deny access
            file_put_contents($logFilePath . '/.htaccess', "Deny from all");
        }

        $logFileName = date('Y-m-d') . '.log';
        $logFilePath = $logFilePath . '/' . $logFileName;

        if ( !file_exists($logFilePath) ) {
            file_put_contents($logFilePath, '');
        }

        return $logFilePath;
    }
}