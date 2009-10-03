<?php
/**
 * Logging support.
 *
 * @package OpenID.Logger
 */
/***/

require_once('OpenID/Logger/Base.php');

/**
 * Logs into PHP error log.
 *
 * @package OpenID.Logger
 */
class OpenID_Logger_PhpErrorLog extends OpenID_Logger_Base
{
    protected function logLine($line)
    {
        error_log($line);
    }
}
?>
