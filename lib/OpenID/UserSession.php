<?php
/**
 * Access to the Front End user session.
 *
 * @package OpenID
 */
/***/

require_once('IllegalArgumentException.php');
require_once('OpenID/User.php');

/**
 * Represents a session with the End-User.
 *
 * This uses builtin PHP session for storage.
 *
 * NB: for not conflicting with the application session data, we
 *     store all the values of this session inside the array
 *     $_SESSION[PHP_SESSION_KEY].
 *
 * @package OpenID
 */
class OpenID_UserSession
{
    /**
     * This user session will be stored inside $_SESSION[PHP_SESSION_KEY].
     */
    const PHP_SESSION_KEY = 'OpenID_UserSession';

    private $data;

    /**
     * Opens an existing session (Iff the user is authenticated).
     *
     * @throws IllegalArgumentException When the session could not be opened
     * @return OpenID_UserSession
     */
    static public function open()
    {
        $log = OpenID_Config::logger();
        $log->debug('openning user session');
        # refrain from openning a session when we are unit testing this.
        # TODO check session integrity somehow?
        if (!headers_sent()) {
            if (!OpenID_User::loggedIn()) {
                $log->debug('not openning user session because there is no user logged in');
                return null;
            }
            if (!session_id()) {
                if (isset($_COOKIE[session_name()]))
                    $log->debug('starting existing PHP session to open user session');
                else
                    $log->debug('starting new PHP session to open user session');
                session_start();
            }
        } else {
            $log->debug('not openning PHP session because the HTTP headers were already sent');
        }
         
        if (!@array_key_exists(self::PHP_SESSION_KEY, $_SESSION)) {
            $log->debug('adding our key into PHP session');
            $_SESSION[self::PHP_SESSION_KEY] = array();
        }
        if ($log->isDebug()) {
            $log->debug('PHP session content '.var_export($_SESSION, true));
            $log->debug('user session openned');
            $log->debug('user session content: '.var_export($_SESSION[self::PHP_SESSION_KEY], true));
        }
        return new OpenID_UserSession($_SESSION[self::PHP_SESSION_KEY]);
    }

    /**
     * Creates a new session.
     *
     * @return OpenID_UserSession
     */
    static public function create()
    {
        $log = OpenID_Config::logger();
        # refrain from openning a session when we are unit testing this.
        if (!headers_sent()) {
            $log->debug('starting PHP session to create user session');
            session_start();
        }
        $log->debug('user session created');
        $data = array();
        return new OpenID_UserSession($data);
    }

    /**
     * Destroys the session.
     *
     * NB: This is also a static function.
     */
    public function destroy()
    {
        unset($_SESSION[self::PHP_SESSION_KEY]);
        if (isset($this))
            $this->data = null;
    }

    /** @return array array of string with all the data keys stored in this session */
    public function all()
    {
        return array_keys($this->data);
    }

    /**
     * Retrieves the value associated with a key.
     *
     * @param string $key the key name we want to retreive the value
     * @param mixed $default the value to return when the key does no exists
     * @return mixed the value of the associated key, or $default if key does not exists
     */
    public function get($key, $default=null)
    {
        $value = @$this->data[$key];
        return $value === null ? $default : $value;
    }

    /**
     * Set the value associated with a key.
     *
     * @param string $key the key name we want to set the value to
     * @param mixed $value the value to associate with $key
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Delete the given key and respective value from this session.
     *
     * @param string $key the key name we want to delete
     */
    public function delete($key)
    {
        unset($this->data[$key]);
    }

    private function __construct(&$data)
    {
        $_SESSION[self::PHP_SESSION_KEY] =& $data;
        $this->data =& $data;
    }
}
?>
