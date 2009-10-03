<?php
require_once('global.php');
require_once('OpenID/Identifier.php');

$log = OpenID_Config::logger();
$log->debug('SELF_URL='.OpenID_Config::selfUrl());

$redirect_url = @$_REQUEST['redirect_url'];
$errors = array();
$user = null;

if (@$_POST['login']) {
    $user = $_POST['user'];
    $pass = $_POST['password'];

    $identity = OpenID_Identifier::findByUsername($user);

    if ($pass != 'password' || !$identity) {
        $errors[]= 'Unknown user or password.';
    } else {
        # store the user in the user-agent session.
        session_start();
        $_SESSION['user'] = $user;

        $log->debug("successful login $user");

        if ($redirect_url) {
            header('HTTP/1.0 302 Go gadget go!');
            header("Location: $redirect_url");
            header('Content-type: text/plain; charset=UTF-8');
            echo 'Your User-Agent should have redirected you!';
            exit;
        } else {
            # no redirect_url?!  Oh well, leave the user here.
            header('Content-type: text/plain; charset=UTF-8');
            echo "You are successfully logged in, but there was no redirect_url variable, so you are stuck here.";
            exit;
        }
    }
}

# fill page variables.
$page = array(
    'title' => 'Login',
    'redirect_url' => $redirect_url,
    'errors' => $errors,
    'user' => $user,
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo h($page['title']); ?> -- Identity Provider</title>
        <style type="text/css">@import "s/p-login.css?1";</style>
    </head>
    <body id="p-login">
        <div id="header">
            <h1>Identity Provider</h1>
        </div>
        <div id="page">
            <div id="page-t">
                <div id="page-b">
                    <div id="page-content">
                        <h2 id="subtitle">Login</h2>
<?php if (@$page['errors']) {?>
                        <div id="errors">
                            <p>Unable to proceed:</p>
                            <ul>
<?php   foreach ($page['errors'] as $error) {?>
                                <li><?php echo h($error); ?></li>
<?php   } ?>
                            </ul>
                        </div>
<?php } ?>
                        <p>To proceed supply your credentials:</p>
                        <form action="login.php" method="post">
                            <input type="hidden" name="redirect_url" value="<?php echo h($page['redirect_url']); ?>" />
                            <fieldset>
                                <table>
                                    <tr>
                                        <td><label for="user" accesskey="n">User</label></td>
                                        <td><input id="user" name="user" value="<?php echo h($page['user']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <td><label for="password" accesskey="e">Password</label></td>
                                        <td><input type="password" id="password" name="password" /></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><input type="submit" id="login" name="login" value="Login" /></td>
                                    </tr>
                                </table>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div id="footer">
            <p>Copyleft 2007 Rui Lopes</p>
        </div>
    </body>
</html>
