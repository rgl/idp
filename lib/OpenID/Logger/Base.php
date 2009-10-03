<?php
/**
 * Logging support.
 *
 * @package OpenID.Logger
 */
/***/

/**
 * Base class that implements the base Logger interface but wihout
 * actually sending the messages anywhere.  Child classes will
 * handle message storage.
 *
 * Every message is prefixed with an unique identifier.  This will
 * help matching log messages from different HTTP requests.
 *
 * Its advisable to install the mod_uniqueid module in Apache,
 * otherwise, we will try to generate an ID for ourselfs, but its
 * not as good... NOR can we match this ID with the Apache logs.
 *
 * Configuration:
 * <pre>
 * logger.level (default: OpenID_Logger::INFO)
 *   One of:
 *      OpenID_Logger::DEBUG
 *      OpenID_Logger::INFO
 *      OpenID_Logger::WARN
 *      OpenID_Logger::ERROR
 *      OpenID_Logger::FATAL
 *
 *   The logging detail level.  The DEBUG level will log all messages;
 *   The INFO level all messages except DEBUG;  The WARN level all
 *   messages except the previous levels, and so on.
 * </pre>
 *
 * @package OpenID.Logger
 */
class OpenID_Logger_Base implements OpenID_Logger
{
    static $names = array(
        'DEBUG',
        'INFO',
        'WARN',
        'ERROR',
        'FATAL'
    );

    protected $l;
    protected $uniqueId;

    function __construct($level=null)
    {
        if ($level === null)
            $level = OpenID_Config::get('logger.level');
        $this->l = $level;
        $this->uniqueId = @$_SERVER['UNIQUE_ID'];
        if ($this->uniqueId === null) {
        	# Oh well, the administrator didn't enable mod_uniqueid :-(
            # Lets create a dummy id ourselfs...
            if (function_exists('posix_getpid'))
                $this->uniqueId = sprintf('%d@%s', posix_getpid(), microtime(true));
            else
                $this->uniqueId = sprintf('@%s', microtime(true));
        }
    }

    function __destruct()
    {
    }

    function debug($message, $exception=null)
    {
        $this->log(self::DEBUG, $message, $exception);
    }

    function isDebug()
    {
        return $this->l <= self::DEBUG;
    }

    function info($message, $exception=null)
    {
        $this->log(self::INFO, $message, $exception);
    }

    function isInfo()
    {
        return $this->l <= self::INFO;
    }

    function warn($message, $exception=null)
    {
        $this->log(self::WARN, $message, $exception);
    }

    function isWarn()
    {
        return $this->l <= self::WARN;
    }

    function error($message, $exception=null)
    {
        $this->log(self::ERROR, $message, $exception);
    }

    function isError()
    {
        return $this->l <= self::ERROR;
    }

    function fatal($message, $exception=null)
    {
        $this->log(self::FATAL, $message, $exception);
    }

    function isFatal()
    {
        return $this->l <= self::FATAL;
    }

    /**
     * This formats the message, and calls logLine which base classes
     * will override to actually send the message to the storage.
     */
    protected function log($level, $message, $exception=null)
    {
        if ($this->l > $level)
            return;
        # TODO escape non ascii chars present on $message.
        #      % => %%
        #      [^A-Za-z0-9] => %XX
        # TODO all lines need to be prefixed with the uniq id
        #      why?  because there are concurrent writers to the log
        #      and their log lines might get intermingled, so we end up
        #      with lines that we don't known where they belong.
        $id = $this->uniqueId;
        #$time = date('c', $_SERVER['REQUEST_TIME']);
        $time = date('c');
        $name = self::$names[$level];
        $line = "$time $name $id $message\n";
        if ($exception)
            $line .= $this->dumpException($exception);
        $this->logLine($line);
    }

    /**
     * Writes the given $line into storage.
     *
     * NB: This MUST be overriden by the child class.
     *
     * @param string $line the line of text to log.
     */
    protected function logLine($line)
    {
    }

    private function dumpException($e)
    {
        return
              " Exception\n"
            . preg_replace('/^/m', '  ', $e->getMessage())
            . "\n"
            . " Stack\n"
            . preg_replace('/^/m', '  ', $e->getTraceAsString())
            . "\n"
            ;
    }
}
?>
