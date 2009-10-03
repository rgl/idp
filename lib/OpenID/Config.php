<?php
/**
 * OpenID Association classes.
 *
 * @package OpenID
 */
/***/

require_once('OpenID/Logger.php');
require_once('OpenID/QueryString.php');

/**
 * Contains all the application settings.
 *
 * You can setup the following settings:
 *
 * <pre>
 * profile  (default: 'test')
 *   The configuration profile you want to use.
 *
 *   NB: if 'profile.deduce' is enabled, then external users can
 *       override (when allowed) this.
 *
 * profile.deduce  (default: null)
 *   When set to a non-null value, this class will automaticaly deduce
 *   the configuration profile from the given _REQUEST variable.
 *
 *   For example, with profile.deduce=X-APP_PROFILE, the profile will
 *   be set to the value $_REQUEST['X-APP_PROFILE'].
 *
 *   NB: You can only select a profile from the set:
 *         [test, development, production]
 *   NB: An invalid profile value will be ignored, and the "test"
 *       profile will be used.
 *
 * profile.deduce.allow_from (default: '127.0.0.1')
 *   Peer IP address that is allowed to set the profile.
 *
 *
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
 *
 * logger.class (default: 'OpenID_Logger_File')
 *   Class name that will handle logging.
 *
 *   As bundled with this library, you can use:
 *     OpenID_Logger_File
 *     OpenID_Logger_PhpErrorLog
 *
 * logger.file.path (default: OpenID_Config::path('log/{profile}.log'))
 *   Path to the file the logger class will store the messages.
 *
 *   NB: This is used by the OpenID_Logger_File logger.
 *
 *
 * db.dsn (default: null)
 *   PHP Data Objects (PDO) Data Source Name (DSN) to access the
 *   database.
 *
 *   See http://php.net/manual/en/function.PDO-construct.php
 *
 * db.user (default: null)
 *   Username to access the database.
 *
 * db.pass (default: null)
 *   Password to access the database.
 *
 * db.opts (default: null)
 *   Extra options to pass into the PDO driver.
 *
 * db.table.prefix (default: 'idp_')
 *   Apply this prefix to all table names we internally use.
 *
 *
 * idp.association.lifetime (default: 14 days)
 *   OpenID Association Lifetime in seconds.
 *
 * idp.provider.url
 *   URL to the Provider page.
 *
 * idp.identity.url
 *   URL to the Identity page.
 *
 * idp.login.url
 *   URL to the Login page.
 *
 * idp.trust.url
 *   URL to the Trust page.
 *
 * idp.persona.url
 *   URL to the Persona edit page.
 *
 *
 * user.provider.class (default: OpenID_UserFromSessionProvider)
 *   Class name that provides the logged in user information.
 * user.mapper.class (default: OpenID_UserMapper)
 *   Class we use to map between user and identity URLs.
 * </pre>
 *
 * @package OpenID
 */
class OpenID_Config
{
    private static $selfUrl = null;
    private static $profile = null;
    private static $path = null;
    private static $config = array();
    private static $logger = null;
    private static $stack;
    private static $userMapper = null;

    /**
     * Initializes the configuration.
     *
     * NB: This is called automatically at the end of this file.
     */
    static function initialize()
    {
        # make sure the basic requirements are met.
        if (version_compare(phpversion(), '5.1.0', 'lt'))
            die('The required minimum version of PHP is 5.1.0, you are running ' . phpversion());
        foreach (explode(',', 'bcmath,mhash,curl') as $n)
            if (!extension_loaded($n)) die("You MUST enable the $n extension.");
        unset($n);

        # When using FastCGI PHP messes with PATH_INFO, so we have to
        # use ORIG_PATH_INFO.  It also fails to append PATH_INFO into
        # PHP_SELF.
        if (@$_SERVER['ORIG_PATH_INFO'] && !@$_SERVER['PATH_INFO']) {
            $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
            $_SERVER['PHP_SELF'] .= $_SERVER['ORIG_PATH_INFO'];
        }

        # build the absolute URL for the current page.
        $isSecure = @$_SERVER['HTTPS'] == 'on';
        $defaultPort = $isSecure ? 443 : 80;
        self::$selfUrl =
            'http'.($isSecure ? 's' : '').'://'.@$_SERVER['HTTP_HOST']
            .(@$_SERVER['SERVER_PORT'] == $defaultPort ? '' : ':'.@$_SERVER['SERVER_PORT'])
            .$_SERVER['PHP_SELF']
            ;

        #
        # Default configuration follows.
        #

        #
        # Profile.
        OpenID_Config::set(array(
            # the profile we want to use.
            # NB: if 'profile.deduce' is enabled, then external users can
            #     override (when allowed) this.
            'profile' => 'test',
            # allow automatic profile deduction from the given _REQUEST variable.
            # or null to disable profile deduction
            'profile.deduce' => null,
            # only allow automatic profide deduction from the given IP address.
            'profile.deduce.allow_from' => '127.0.0.1',
        ));


        #
        # Logger.

        OpenID_Config::set(array(
            'logger.level' => OpenID_Logger::INFO,
            'logger.file.path' => OpenID_Config::path('log/{profile}.log'),
            'logger.class' => 'OpenID_Logger_File',
        ));


        #
        # Database.

        OpenID_Config::set(array(
            'db.table.prefix' => 'idp_',
        ));


        #
        # OpenID protocol.

        OpenID_Config::set(array(
            # Association lifetime [seconds].
            'idp.association.lifetime' => 14*24*60*60, # 14 days
            # The number of secrets to generate in a lifetime span.
            'idp.association.secret.count' => 50,
        ));


        #
        # User.

        OpenID_Config::set(array(
            # the class we use to extract the current logged user.
            'user.provider.class' => 'OpenID_UserFromSessionProvider',
            # the class we use to map between user and identity URLs.
            'user.mapper.class' => 'OpenID_UserMapper'
        ));
    }

    /**
     * Constructs an URL to the current page.
     *
     * @param string $path extra path segments to append
     * @param hash $qs query string parameters to append
     * @return string the combined URL
     */
    static function selfUrl($path=null, $qs=null)
    {
        return self::mergeUrl(self::$selfUrl, $path, $qs);
    }

    /**
     * Constructs an URL to the IdP Provider Endpoint.
     *
     * @param hash $data query string parameters to append
     * @return string the combined URL
     */
    static function providerUrl($data=null)
    {
        $url = self::get('idp.provider.url');
        return OpenID_QueryString::merge($url, $data);
    }

    /**
     * Constructs an URL for the given $user identity.
     *
     * @param string $user the user
     * @return string the combined URL
     */
    static function identityUrl($user=null)
    {
        return self::userMapper()->userToIdentity($user);
    }

    /**
     * Constructs an URL to the GUI Endpoint that handles the user login.
     *
     * @param hash $data query string parameters to append
     * @return string the combined URL
     */
    static function loginUrl($data=null)
    {
        $url = self::get('idp.login.url');
        return OpenID_QueryString::merge($url, $data);
    }

    /**
     * Constructs an URL to the GUI Endpoint that handles the user
     * authentication into some consumer.
     *
     * @param hash $data query string parameters to append
     * @return string the combined URL
     */
    static function trustUrl($data=null)
    {
        $url = self::get('idp.trust.url');
        return OpenID_QueryString::merge($url, $data);
    }

    /**
     * Constructs an URL to the GUI Endpoint that handles the
     * Persona configuration.
     *
     * @param hash $data query string parameters to append
     * @return string the combined URL
     */
    static function personaUrl($data=null)
    {
        $url = self::get('idp.persona.url');
        return OpenID_QueryString::merge($url, $data);
    }

    /**
     * Constructs an URL.
     *
     * @param string $url base URL
     * @param string $path extra path segments to append
     * @param hash $qs query string parameters to append
     * @return string the combined URL
     */
    static function mergeUrl($url, $path, $qs=null)
    {
        if ($path) {
            # TODO remove relative segments
            # TODO deal with absolute path
            $url .= '/'.$path;
        }
        if ($qs)
            return OpenID_QueryString::merge($url, $qs);
        return $url;
    }

    /**
     * Returns the singleton that handle the mapping of users to
     * identities.
     *
     * This will use the class defined by the configuration
     * option "user.mapper.class".
     *
     * @return OpenID_UserMapper the user mapper
     * @see OpenID_RegexUserMapper
     */
    public static function userMapper()
    {
        if (!self::$userMapper) {
            $class = self::get('user.mapper.class');
            $path = str_replace('_', '/', $class).'.php';
            require_once($path);
            self::$userMapper = new $class();
        }
        return self::$userMapper;
    }

    /**
     * Constructs a FS path relative to the directory above the "lib/"
     * directory where this file is located.
     *
     * @param string extra path segments to append.
     * @return string resulting path
     */
    public static function path($extra=null)
    {
        $path = self::$path;
        if (!$path)
        	$path = self::$path = realpath(dirname(__FILE__).'/../..').'/';
        return $path.$extra;
    }

    /**
     * Returns the singleton that handles logging.
     *
     * This will use the class defined by the configuration
     * option "logger.class".
     *
     * @return OpenID_Logger the logger
     */
    public static function logger()
    {
        if (!self::$logger) {
            $class = self::get('logger.class');
            $path = str_replace('_', '/', $class).'.php';
            require_once($path);
            self::$logger = new $class();
        }
        return self::$logger;
    }

    /**
     * @return string profile name
     */
    public static function profile()
    {
        if (!self::$profile)
        	self::$profile = self::profileDeduce();
        return self::$profile;
    }

    /**
     * Deduces the profile name we should use given the environment.
     *
     * When the configuration "profile.deduce" is defined, this will
     * deduce the profile name from the current
     * $_REQUEST[$'profile.deduce'] variable, iif $_SERVER['REMOTE_ADDR']
     * matches the configuration "profile.deduce.allow_from".
     *
     * If the above fails, or is disabled, the profile name defined by
     * the configuration "profile" will be used.
     *
     * @return string profile name
     */
    private static function profileDeduce()
    {
        $deduce = self::get('profile.deduce');
        if ($deduce) {
            if (@$_REQUEST[$deduce] && @$_SERVER['REMOTE_ADDR'] == self::get('profile.deduce.allow_from')) {
                $profile = $_REQUEST[$deduce];
                if (in_array($profile, array('test', 'development', 'production'))) {
                    self::set('profile', $profile);
                    return $profile;
                }
            }
        }
        return self::get('profile');
    }

    /**
     * Get a configuration value.
     *
     * @param string $key the configuration name to obtain
     * @param mixed $default this will be returned when the configuration name does not exists
     * @return mixed the configuration value
     */
    public static function get($key, $default=null)
    {
        if (array_key_exists($key, self::$config))
            return self::$config[$key];
        return $default;
    }

    /**
     * Get a configuration value.
     *
     * This is similar to get but also expands (by calling get) the variables.
     *
     * Example:
     * <code>
     * set('a', 'A');
     * set('b', 'B');
     * set('combined', 'combined={a}{b}');
     * $result = expanded('combined');
     * # => combined=AB
     * </code>
     *
     * @param string $key the configuration name to obtain
     * @param mixed $default this will be returned when the configuration name does not exists
     * @return mixed the configuration value
     */
    public static function expanded($key, $default=null)
    {
        $value = self::get($key, $default);
        if ($value) {
            $value = preg_replace_callback(
                '/([^{])?{([a-z0-9\.]+)}/i',
                create_function(
                    '$matches',
                    'return $matches[1].OpenID_Config::get($matches[2]);'
                ),
                $value
            );
            $value = str_replace('{{', '{', $value);
        }
        return $value;
    }

    /**
     * Set one or several configuration values.
     *
     * @param string|hash $keyOrHash the configuration names (and values when $keyOrHash is a hash) to set
     * @param mixed $value when $keyOrHash is NOT an array this will set the $keyOrHash value;  otherwise, this will not but used.
     */
    public static function set($keyOrHash, $value=null)
    {
        if (is_array($keyOrHash)) {
        	foreach ($keyOrHash as $key => $value)
        		self::$config[$key] = $value;
        } else
            self::$config[$keyOrHash] = $value;
    }

    /**
     * Opens a connection to the database.
     *
     * The following configurations will be used:
     *
     * <pre>
     * db.dsn (default: null)
     *   PHP Data Objects (PDO) Data Source Name (DSN) to access the
     *   database.
     *
     *   See http://php.net/manual/en/function.PDO-construct.php
     *
     * db.user (default: null)
     *   Username to access the database.
     *
     * db.user (default: null)
     *   Password to access the database.
     *
     * db.opts (default: null)
     *   Extra options to pass into the PDO driver.
     *
     * db.table.prefix (default: 'idp_')
     *   Apply this prefix to all table names we internally use.
     * </pre>
     *
     * @return PDO database connection
     * @exception Exception
     */
    public static function db()
    {
        $log = self::logger();
        # NB: You should create the database using the schema.mysql.sql
        #     script.
        $db = new PDO(
            self::expanded('db.dsn'),
            self::get('db.user'),
            self::get('db.pass'),
            self::get('db.opts')
        );
        try {
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (Exception $e) {
            $log->warn('Failed to open database', $e);
            # clean up.
            $db = null;
            throw $e;
        }
    }

    /**
     * This is used by the Unit Testing code to reconfigure the whole
     * configuration while the test is running.
     */
    static function _push()
    {
        if (self::$stack === null)
            self::$stack = array();
        self::$stack[] = array(
            'profile' => self::$profile,
            'path' => self::$path,
            'config' => self::$config,
            'logger' => self::$logger,
        );
        self::$profile = null;
        self::$path = null;
        self::$config = array();
        self::$logger = null;
    }

    /**
     * This is used by the Unit Testing code to reconfigure the whole
     * configuration while the test is running.
     */
    static function _pop()
    {
        $data = array_pop(self::$stack);
        self::$profile = $data['profile'];
        self::$path = $data['path'];
        self::$config = $data['config'];
        self::$logger = $data['logger'];
    }
}

# Initialize the configuration.
OpenID_Config::initialize();
?>
