<?php
require_once('global.php');
require_once('OpenID/Helper.php');
require_once('OpenID/User.php');
require_once('OpenID/UserSession.php');
require_once('OpenID/QueryString.php');
require_once('OpenID/Identifier.php');
require_once('OpenID/TrustRoot.php');
require_once('OpenID/Persona.php');
require_once('OpenID/UI/PersonaHelper.php');
require_once('OpenID/SR/Country.php');
require_once('OpenID/SR/Language.php');
require_once('OpenID/SR/TimeZone.php');

$log = OpenID_Config::logger();
$log->debug('SELF_URL='.OpenID_Config::selfUrl());

$user = OpenID_User::loggedIn();
if (!$user) {
	# TODO raise error with user unauthenticated.
    die("you are not logged in!");
    exit;
}
$identity = $user->identity();
$redirect_url = @$_REQUEST['redirect_url'];

# if its a postback, handle it.
$type = null;
if (@$_POST['save'])
    $type = 'save';
elseif (@$_POST['continue'])
    $type = 'continue';
elseif (@$_POST['delete'])
    $type = 'delete';
if ($type) {
    $log->debug("handling postback");

    switch ($type) {
        case 'save':
        case 'continue':
            $personaId = @$_REQUEST['persona'];
            # create a new persona?
            if ($personaId == 0) {
                $persona = OpenID_Persona::create($identity, @$_POST['name']);
            } else {
                $persona = OpenID_Persona::findById($identity, $personaId);
            }
            if (!$persona) {
                # TODO raise error with invalid persona.
                die("unknown persona!");
                exit;
            }

            $persona->setName(@$_POST['name']);
            $persona->setSrNickname(@$_POST['sr_nickname']);
            $persona->setSrFullname(@$_POST['sr_fullname']);
            $persona->setSrEmail(@$_POST['sr_email']);
            $persona->setSrDobYear((int) @$_POST['sr_dob_year']);
            $persona->setSrDobMonth((int) @$_POST['sr_dob_month']);
            $persona->setSrDobDay((int) @$_POST['sr_dob_day']);
            $persona->setSrGender(@$_POST['sr_gender']);
            $persona->setSrPostalcode(@$_POST['sr_postalcode']);
            $persona->setSrCountry(@$_POST['sr_country']);
            $persona->setSrLanguage(@$_POST['sr_language']);
            $persona->setSrTimezone(@$_POST['sr_timezone']);
            $persona->save();
            # TODO log this save.

            if ($type != 'continue')
                break;

            $location = $redirect_url;
            $log->debug("Redirect to $location");
            header('HTTP/1.0 302 Found');
            header("Location: $location");
            header('Content-type: text/plain; charset=UTF-8');
            echo 'Found.';
            exit;

         case 'delete':
            $personaId = @$_REQUEST['persona'];
            $persona = OpenID_Persona::findById($identity, $personaId);
            if ($persona) {
                # TODO log this delete.
                $persona->delete();
                $persona = null;
            }
            break;
    }
} else {
    # persona == 0 is a special case.  it means the user wants to create
    # a new persona.
    $personaId = intval(@$_REQUEST['persona']);
    if ($personaId) {
    	$persona = OpenID_Persona::findById($identity, $personaId);
        if (!$persona) {
            # TODO raise error with invalid persona.
            #      maybe we should just ignore that, and just edit the
            #      first persona?  but, showing a warning or something like
            #      that on the page?
            die("unknown persona!");
        }
    } else
        $persona = null;
}

$errors = array();

$personasSorted = array();
$personas = array();
$personaAttributes = OpenID_Persona::attributeNames();
$identityPersonas = $identity->personas();
if ($identityPersonas) {
    foreach ($identityPersonas as $p) {
        $fields = array();
        foreach ($personaAttributes as $name)
            $fields[$name] = $p->get($name);
        $personas[$p->id()] = array(
            'name' => $p->name(),
            'fields' => $fields
        );
        $personasSorted[] = array($p->id(), $p->name());
    }
    if (!count($personas))
        $personas = null;
    else {
        # sort the personas by name.
        usort($personasSorted, create_function('$a,$b', 'return strnatcasecmp($a[1], $b[1]);'));
    }
}
if (!$persona)
	$persona = OpenID_Persona::create($identity, 'New Persona');

$months = array(
    array('id' =>  1, 'name' => 'January'),
    array('id' =>  2, 'name' => 'February'),
    array('id' =>  3, 'name' => 'March'),
    array('id' =>  4, 'name' => 'April'),
    array('id' =>  5, 'name' => 'May'),
    array('id' =>  6, 'name' => 'June'),
    array('id' =>  7, 'name' => 'July'),
    array('id' =>  8, 'name' => 'August'),
    array('id' =>  9, 'name' => 'September'),
    array('id' => 10, 'name' => 'October'),
    array('id' => 11, 'name' => 'November'),
    array('id' => 12, 'name' => 'December')
);


# fill page variables.
$page = array(
    # Page title.
    'title'             => 'Persona Editor',
    # URL where we should redirect to after we are done.
    'redirect_url'      => $redirect_url,
    # user identity.
    'identity'          => $identity->identity(),
    # user selected persona.
    'persona'           => $persona,
    # user personas sorted by user name.
    'personasSorted'    => $personasSorted,
    # user personas.
    'personas'          => $personas,
    # all the countries.
    'countries'         => OpenID_SR_Country::all(),
    # all the languages.
    'languages'         => OpenID_SR_Language::all(),
    # all the time zones.
    'timezones'         => OpenID_SR_TimeZone::all(),
    # the lowest year we show on the combo box.
    'dob_year_start'    => 1930,
    # the highest year we show on the combo box.
    'dob_year_end'      => date('Y') - 1,
    # all months.
    'months'            => $months,
    # all (possible) errors.
    'errors'            => $errors,
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo h($page['title']); ?> -- Identity Provider</title>
        <style type="text/css">@import "s/p-persona.css?1";</style>
        <script type="text/javascript">
            var personas = <?php echo json_encode($page['personas']); ?>;

            // an object to do HTML manipulations.
            // eg: use HTML.escape to escape an text.
            var HTML = (function() {
                var div = document.createElement('div');
                var text = document.createTextNode('');
                div.appendChild(text);
                return {
                    escape: function(textToEscape) {
                        text.data = textToEscape;
                        return div.innerHTML;
                    }
                };
            })();

            function setInputText(inputId, dataId, data) {
                var input = document.getElementById(inputId);
                input.value = data[dataId] ? data[dataId] : '';
            }

            function setSelectText(selectId, dataId, data) {
                var select = document.getElementById(selectId);
                var value = data[dataId];
                var options = select.options;
                for (var n = 0; n < options.length; ++n) {
                    var option = options[n];
                	if (option.value == value) {
                        select.selectedIndex = n;
                		return;
                	}
                }
                select.selectedIndex = 0;
            }

            function selectPersona(id) {
            	var data = personas[id];
                if (!data)
                    data = {name: 'New Persona'};
                var srData = data['fields'];
                if (!srData)
                    srData = {};

                setInputText('name', 'name', data);
                setInputText('sr_nickname', 'nickname', srData);
                setInputText('sr_email', 'email', srData);
                setInputText('sr_fullname', 'fullname', srData);
                var dob = srData['dob'];
                if (!dob) dob = '0000-00-00';
                dob = dob.match(/(\d+)-(\d+)-(\d+)/);
                dob = {
                    year:  parseInt(dob[1]),
                    month: parseInt(dob[2]),
                    day:   parseInt(dob[3])
                };
                setInputText('sr_dob_year', 'year', dob);
                setInputText('sr_dob_month', 'month', dob);
                setInputText('sr_dob_day', 'day', dob);
                setSelectText('sr_gender', 'gender', srData);
                setInputText('sr_postalcode', 'postalcode', srData);
                setSelectText('sr_country', 'country', srData);
                setSelectText('sr_language', 'language', srData);
                setSelectText('sr_timezone', 'timezone', srData);
            }

            window.onload = function() {
                var persona = document.getElementById('persona');
                persona.onchange = function() {
                    var option = persona.options[persona.selectedIndex];
                    var personaId = option.value;
                    if (!personaId)
                        return;
                    selectPersona(personaId);
                };
                // In FireFox when we press the up/down key there is no
                // onchange event... so we manually call the onchange
                // event handler here.
                persona.onkeyup = function(e) {
                    if (!e || !e.DOM_VK_UP)
                        return;
                	if (e.keyCode == e.DOM_VK_UP || e.keyCode == e.DOM_VK_DOWN) {
                        persona.onchange();
                	}
                };
            };
        </script>
    </head>
    <body id="p-persona">
        <div id="header">
            <h1>Identity Provider</h1>
        </div>
        <div id="page">
            <div id="page-t">
                <div id="page-b">
                    <div id="page-content">
                        <h2 id="subtitle">Trust</h2>
<?php if (@$page['errors']) { ?>
                        <div id="errors">
                            <p>Unable to proceed:</p>
                            <ul>
<?php   foreach ($page['errors'] as $error) { ?>
                                <li><?php echo h($error); ?></li>
<?php   } ?>
                            </ul>
                        </div>
<?php } ?>
                        <form action="persona.php" method="post">
                            <input type="hidden" name="redirect_url" value="<?php echo h($page['redirect_url']); ?>" />
                            <fieldset id="persona-fields">
                                <legend>
                                    Persona:
                                    <select id="persona" name="persona">
                                        <option value="0"<?php if ($page['persona']->id() == 0) echo ' selected="selected"'; ?>>New Persona</option>
<?php   foreach ($page['personasSorted'] as $persona) { list($id, $name) = $persona; ?>
                                        <option value="<?php echo h($id); ?>"<?php if ($page['persona']->id() == $id) echo ' selected="selected"'; ?>>
                                            <?php echo h($name); ?>
                                        </option>
<?php   } ?>
                                    </select>
                                </legend>
                                <table>
                                    <tbody>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h('Persona Name')); ?>:</th>
                                            <td><input type="text" id="name" name="name" value="<?php echo h($page['persona']->name()); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th>&nbsp;</th>
                                            <td><input type="checkbox" id="default" name="default" value="TODO" /> <label for="default">Make this the default persona</label></td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('nickname'))); ?>:</th>
                                            <td><input type="text" id="sr_nickname" name="sr_nickname" value="<?php echo h($page['persona']->srNickname()); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('fullname'))); ?>:</th>
                                            <td><input type="text" id="sr_fullname" name="sr_fullname" value="<?php echo h($page['persona']->srFullname()); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('email'))); ?>:</th>
                                            <td><input type="text" id="sr_email" name="sr_email" value="<?php echo h($page['persona']->srEmail()); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('dob'))); ?>:</th>
                                            <td>
                                                <select id="sr_dob_year" name="sr_dob_year">
                                                    <option value="">--</option>
<?php for ($year = $page['dob_year_start']; $year <= $page['dob_year_end']; ++$year) { ?>
                                                    <option value="<?php echo $year; ?>"<?php echo $page['persona']->srDobYear() == $year ? ' selected="selected"' : ''; ?>><?php echo $year; ?></option>
<?php } ?>
                                                </select>
                                                <select id="sr_dob_month" name="sr_dob_month">
                                                    <option value="">--</option>
<?php foreach ($page['months'] as $month) { ?>
                                                    <option value="<?php echo $month['id']; ?>"<?php echo $page['persona']->srDobMonth() == $month['id'] ? ' selected="selected"' : ''; ?>><?php echo $month['name']; ?></option>
<?php } ?>
                                                </select>
                                                <select id="sr_dob_day" name="sr_dob_day">
                                                    <option value="">--</option>
<?php for ($day = 1; $day <= 31; ++$day) { ?>
                                                    <option value="<?php echo $day; ?>"<?php echo $page['persona']->srDobDay() == $day ? ' selected="selected"' : ''; ?>><?php echo $day; ?></option>
<?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('postalcode'))); ?>:</th>
                                            <td><input type="text" id="sr_postalcode" name="sr_postalcode" value="<?php echo h($page['persona']->srPostalcode()); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('gender'))); ?>:</th>
                                            <td>
                                                <select id="sr_gender" name="sr_gender">
                                                    <option value="">--</option>
                                                    <option value="M"<?php echo h($page['persona']->srGender() == 'M' ? ' selected="selected"' : ''); ?>>Male</option>
                                                    <option value="F"<?php echo h($page['persona']->srGender() == 'F' ? ' selected="selected"' : ''); ?>>Female</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('country'))); ?>:</th>
                                            <td>
                                                <select id="sr_country" name="sr_country">
                                                    <option value="">--</option>
<?php foreach ($page['countries'] as $country) { ?>
                                                    <option value="<?php echo h($country->code()); ?>"<?php echo $page['persona']->srCountry() == $country->code() ? ' selected="selected"' : ''; ?>><?php echo h($country->name()); ?></option>
<?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('timezone'))); ?>:</th>
                                            <td>
                                                <select id="sr_timezone" name="sr_timezone">
                                                    <option value="">--</option>
<?php foreach ($page['timezones'] as $timezone) { ?>
                                                    <option value="<?php echo h($timezone->code()); ?>"<?php echo $page['persona']->srTimezone() == $timezone->code() ? ' selected="selected"' : ''; ?>><?php echo h($timezone->name()); ?></option>
<?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php echo str_replace(' ', '&nbsp;', h(OpenID_UI_PersonaHelper::label('language'))); ?>:</th>
                                            <td>
                                                <select id="sr_language" name="sr_language">
                                                    <option value="">--</option>
<?php foreach ($page['languages'] as $language) { ?>
                                                    <option value="<?php echo h($language->code()); ?>"<?php echo $page['persona']->srLanguage() == $language->code() ? ' selected="selected"' : ''; ?>><?php echo h($language->name()); ?></option>
<?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </fieldset>
                            <p>
                                <input type="submit" id="save" name="save" value="Save" />
<?php if ($page['redirect_url']) { ?>
                                <input type="submit" id="continue" name="continue" value="Save, and Continue" />
<?php } ?>
                                <input type="submit" id="delete" name="delete" value="Delete" />
                            </p>
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
