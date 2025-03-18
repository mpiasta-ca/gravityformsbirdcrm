<?php

class GF_BirdCRM_LogFactory
{
    public const LEVEL_OFFLINE = 0; // None
    public const LEVEL_ERROR = 1; // Error only
    public const LEVEL_DELTA = 2; // Delta, Error only
    public const LEVEL_DEBUG = 3; // Debug, Delta, Error (all)

    private $className;
    private $logLevel;

    /** @var GF_BirdCRM_LogService */
    private $logService;

    public function __construct($className, $logLevel = self::LEVEL_DEBUG)
    {
        $this->logService = GF_BirdCRM_LogService::get_instance();
        $this->className = $className;
        $this->logLevel = $logLevel;
    }

    public function debug($funcName, $message, $maxLength = null)
    {
        if ($this->logLevel >= self::LEVEL_DEBUG) {
            $message = $this->trimStringLength($message, $maxLength);
            $this->logService->log($this->className . '::' . $funcName, $message);
        }
    }

    public function delta($funcName, $message, $maxLength = null)
    {
        if ($this->logLevel >= self::LEVEL_DELTA) {
            $message = $this->trimStringLength($message, $maxLength);
            $this->logService->log($this->className . '::' . $funcName, $message);
        }
    }

    public function error($funcName, $message, $maxLength = null)
    {
        if ($this->logLevel >= self::LEVEL_ERROR) {
            $message = $this->trimStringLength($message, $maxLength);
            $this->logService->log($this->className . '::' . $funcName, $message);
        }
    }

    private function trimStringLength($message, $maxLength = null)
    {
        if ( empty($maxLength) || strlen($message) <= $maxLength ) {
            return $message;
        }

        return substr($message, 0, $maxLength) . '[...]';
    }
}