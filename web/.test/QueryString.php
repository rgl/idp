<?php
require_once('../global.php');
require_once('OpenID/QueryString.php');

/**
 * This file will echo the data that was received in the QueryString or
 * in the POST body when the content type is
 * "application/x-www-form-urlencode".
 */
function echoData($data)
{
    $data = array_change_key_case($data);
    $keys = array_keys($data);
    sort($keys);
	foreach ($keys as $k) {
        $v = $data[$k];
		echo $k, '=', $v, "\n";
	}
}

$data = array(
    'content-length' => @$_SERVER['CONTENT_LENGTH'],
    'content-type'   => @$_SERVER['CONTENT_TYPE'],
    'request-method' => @$_SERVER['REQUEST_METHOD'],
);

header('Content-type: text/plain; charset=UTF-8');
echoData($data);
echoData(OpenID_QueryString::decodeEnvironment());
?>
