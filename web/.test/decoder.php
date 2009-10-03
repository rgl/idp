<?php
# this is a utility page that I use for decoding data related to OpenID.
require_once('../global.php');

switch (@$_REQUEST['c']) {
    case 'qs':
        require_once('OpenID/QueryString.php');
        $qs = OpenID_QueryString::decode($_REQUEST['qs']);
        break;
    case 'tidy':
        $config = array(
            'indent' => true,
            'output-xhtml' => true,
            'wrap' => 200
        );
        $tidy = tidy_parse_string($_REQUEST['tidy'], $config, 'UTF8');
        break;
}
?>
<html>
<body>
<fieldset>
    <legend>Query String Decoder</legend>
    <form action="<?php echo $_SERVER['PHP_SELF'].'?c=qs';?>" method="POST">
        <p><input type="text" name="qs" size="100" /></p>
        <p><input type="submit" name="submit" value="Decode" /></p>
    </form>
<?php
if (isset($qs)) {
    echo '<table>';
    foreach ($qs as $k => $v) {
        echo '<tr><td>';
        echo h($k);
        echo '</td><td>';
        echo h($v);
        echo '</td></tr>';
    }
    echo '</table>';
}
?>
</fieldset>
<fieldset>
    <legend>HTML Tidy</legend>
    <form action="<?php echo $_SERVER['PHP_SELF'].'?c=tidy';?>" method="POST">
        <p><textarea name="tidy" rows="5" cols="70"></textarea></p>
        <p><input type="submit" name="submit" value="Tidy" /></p>
    </form>
<?php
if (isset($tidy)) {
    echo '<pre>';
    echo h($tidy);
    echo '</pre>';
}
?>
</fieldset>
</body>
</html>