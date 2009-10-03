<?php
require_once('global.php');
# kill the session data, session cookie, and session.
session_start();
$_SESSION = array();
if (isset($_COOKIE[session_name()]))
    setcookie(session_name(), '', time()-42000, '/');
session_destroy();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Logged out -- Identity Provider</title>
        <style type="text/css">@import "s/p-logout.css?1";</style>
    </head>
    <body id="p-logout">
        <div id="header">
            <h1>Identity Provider</h1>
        </div>
        <div id="page">
            <div id="page-t">
                <div id="page-b">
                    <div id="page-content">
                        <h2 id="subtitle">Logged out</h2>
                        <p>You have been logged out, have a nice day!</p>
                    </div>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyleft 2007 Rui Lopes</p>
        </div>
    </body>
</html>