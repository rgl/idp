<?php
# Configuration settings for the 'test' profile.

error_reporting(E_ALL);
ini_set('display_errors', '1');

OpenID_Config::set(array(
    'idp.provider.url'  => 'http://localhost/prototype/idp.php',
    'idp.identity.url'  => 'http://localhost/prototype/id.php',
    'idp.login.url'     => 'http://localhost/prototype/login.php',
    'idp.trust.url'     => 'http://localhost/prototype/trust.php',
    'idp.persona.url'   => 'http://localhost/prototype/persona.php',
    'logger.level' => OpenID_Logger::DEBUG,
));
?>