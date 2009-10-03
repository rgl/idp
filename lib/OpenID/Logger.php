<?php
/**
 * Logging support.
 *
 * @package OpenID
 */
/***/

/**
 * The logger interface that is used to log messages.
 *
 * The logger has some level associated with it:
 * 
 * - DEBUG  debug messages, and everything bellow.
 * - INFO   informational messages, and everything bellow.
 * - WARN   warning messages, and everything bellow.
 * - ERROR  error messages, and everything bellow.
 * - FATAL  fatal messages.
 *
 * @package OpenID
 */ 
interface OpenID_Logger
{
    const DEBUG = 0;
    const INFO  = 1;
    const WARN  = 2;
    const ERROR = 3;
    const FATAL = 4;

    function debug($message, $exception=null);

    function isDebug();

    function info($message, $exception=null);

    function isInfo();

    function warn($message, $exception=null);

    function isWarn();

    function error($message, $exception=null);

    function isError();

    function fatal($message, $exception=null);

    function isFatal();
}
?>
