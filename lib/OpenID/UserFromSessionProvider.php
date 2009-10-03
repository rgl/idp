<?php
/**
 * OpenID Association classes.
 *
 * @package OpenID
 */
/***/

/**
 * Extracts information about the current logged in user.
 *
 * This simply extracts the user from the PHP session variable "user".
 *
 * @package OpenID
 */
class OpenID_UserFromSessionProvider
{
    /**
     * Initializes this instance.
     *
     * NB: This will open the PHP session iif the user agent sent a cookie.
     */
    function __construct()
    {
        # refrain from openning a session when we are unit testing this.
        if (!headers_sent()) {
            # only start the session if there is a session cookie.
            # and there is no session already started.
            if (isset($_COOKIE[session_name()]) && !session_id())
                session_start();
        }
    }

    /** @return string user name */
    function userName()
    {
        return @$_SESSION['user'];
    }
}
?>
