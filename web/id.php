<?php
require_once('global.php');
require_once('OpenID/Identifier.php');

$log = OpenID_Config::logger();

$server = OpenID_Config::providerUrl();

# make sure we were called with a valid identity.
$identity = OpenID_Identifier::findByIdentity(OpenID_Config::selfUrl());
if (!$identity || $identity->disabled()) {
    header('HTTP/1.0 404 Not Found');
    echo 'Unknown Identity';
    die;
}
?>
<html>
    <head>
        <title>Identity Page for <?php echo h($identity->identity())?>.</title>
        <link rel="openid.server" href="<?php echo h($server)?>" />
    </head>
    <body>
        <h1>Identity Page for <?php echo h($identity->identity())?></h1>
    </body>
</html>
