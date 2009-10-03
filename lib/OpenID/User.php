<?php
/**
 * OpenID Identi(ty|fier) classes.
 *
 * @package OpenID
 */
/***/

require_once('OpenID/Identifier.php');

/**
 * Represents a (logged in) User.
 *
 * Configuration:
 *
 * <pre>
 * user.provider.class (default: OpenID_UserFromSessionProvider)
 *   Class name that provides the logged in user information.
 * </pre>
 *
 * @package OpenID
 * @see OpenID_UserFromSessionProvider
 */
class OpenID_User
{
    private static $provider = null;
    private static $user = null;

    private $name;
    private $identity;

    private function __construct($name)
    {
        $this->name = $name;
    }

    /** @return OpenID_User the current logged in user, or null if no user is logged in */
    static function loggedIn()
    {
        if (!self::$user) {
            $provider = self::provider();
            $name = $provider->userName();
            if ($name)
                self::$user = new OpenID_User($name);
        }
        return self::$user;
    }

    /** @return string this user name */
    function name()
    {
        return $this->name;
    }

    /** @return OpenID_Identity identity associated with this user */
    function identity()
    {
        if (!$this->identity)
            $this->setupIdentity();
        return $this->identity;
    }

    private function setupIdentity()
    {
        $identity = OpenID_Identifier::findByUsername($this->name);
        if (!$identity) {
            # the user does not yet exists, so create a new (unsaved)
            # identity object for the user.
            $identityUrl = OpenID_Config::identityUrl($this->name);
            $identity = OpenID_Identifier::create($identityUrl, $this->name);
        }
        $this->identity = $identity;
    }

    private static function provider()
    {
        if (!self::$provider) {
            $class = OpenID_Config::get('user.provider.class');
            $path = str_replace('_', '/', $class).'.php';
            require_once($path);
            self::$provider = new $class();
        }
        return self::$provider;
    }
}
?>
