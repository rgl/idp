<?php
require_once('OpenID/Config.php');

#
# Configure settings that common to all profiles.

OpenID_Config::set(array(
    # the profile we want to use.
    # NB: if 'profile.deduce' is enabled, then external users can
    #     override this.
    'profile' => 'development',

    # allow automatic profile deduction from the given _REQUEST variable.
    # or null to disable profile deduction.
    # NB: Do not use this in production.
    'profile.deduce' => 'X-APP_PROFILE',

    # Data Source Name to access the database.
    'db.dsn'  => 'mysql:host=localhost;dbname=idp_{profile}',
    # Database username.
    'db.user' => 'user',
    # Database password.
    'db.pass' => 'password',
));

# include profile specific settings (if exists).
@include_once(dirname(__FILE__).'/config-'.OpenID_Config::profile().'.php');
?>