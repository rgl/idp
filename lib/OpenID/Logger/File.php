<?php
/**
 * File Logging support.
 *
 * @package OpenID.Logger
 */
/***/

require_once('OpenID/Logger/Base.php');

/**
 * Logs into a file.
 *
 * Configuration: 
 * <pre>
 * logger.file.path (default: OpenID_Config::path('log/{profile}.log'))
 *   Path to the file the logger class will store the messages.
 * </pre>
 *
 * @package OpenID.Logger
 */
class OpenID_Logger_File extends OpenID_Logger_Base
{
    private $f;

    function __construct($level=null, $path=null)
    {
        parent::__construct($level);
        if ($path === null)
            $path = OpenID_Config::expanded('logger.file.path');
        if (!$path)
            throw IllegalArgumentException('path cannot be empty');
        $this->f = fopen($path, 'a');
        if ($this->f === false) {
            $message = "unable to open logger file at $path... logging to stderr.";
            error_log($message);
            $this->f = fopen('php://stderr', 'w');
            $this->warn($message);
        }
    }

    function __destruct()
    {
        fclose($this->f);
        parent::__destruct();
    }

    protected function logLine($line)
    {
        fwrite($this->f, $line);
    }
}
?>
