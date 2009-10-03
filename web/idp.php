<?php
require_once('global.php');
require_once('OpenID/Provider.php');

# configure PHP for not issuing HTML errors.  Because we are logging
# everything, HTML errors are trashy;  AND because we do not generate
# any HTML here.
ini_set('html_errors', '0');

$log = OpenID_Config::logger();

# Dump dos argumentos do pedido num ficheiro de log.
$data = 'SELF_URL='.OpenID_Config::selfUrl();
foreach (array('GET', 'POST', 'COOKIE') as $name) {
    $container = $GLOBALS["_$name"];
    if (sizeof($container) == 0)
        continue;
    $data .= "\n  $name:\n";
    foreach ($container as $key => $value) {
        $data .= "    $key=" . $value . "\n";
    }
}
$data .= "\n  SERVER:\n";
foreach (array('REMOTE_ADDR', 'QUERY_STRING') as $name) {
    $data .= "    $name=$_SERVER[$name]\n";
}
$log->debug($data);

$provider = new OpenID_Provider();
$provider->handleRequest();
# if we reach here, the request was not an OpenID request.
header('HTTP/1.0 400 Sorry?');
?>
This is an <a href="http://openid.net/">OpenID</a> Identity Provider.
Nothing to see here, please move along.
