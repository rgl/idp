<?php
require_once('global.php');
require_once('OpenID/Helper.php');
require_once('OpenID/User.php');
require_once('OpenID/UserSession.php');
require_once('OpenID/QueryString.php');
require_once('OpenID/Identifier.php');
require_once('OpenID/TrustRoot.php');
require_once('OpenID/UI/PersonaHelper.php');

$log = OpenID_Config::logger();
$log->debug('SELF_URL='.OpenID_Config::selfUrl());

$user = OpenID_User::loggedIn();
# make sure the user is authenticated.
if (!$user) {
    header('HTTP/1.0 400 Who are you?');
    header('Content-type: text/plain; charset=UTF-8');
    echo 'Who are you?';
    exit;
}

$redirect_url = @$_REQUEST['redirect_url'];
$log->debug("redirect_url=$redirect_url");
$queryString = OpenID_QueryString::decodeURI($redirect_url);
$request = OpenID_Helper::parametersFromRequest($queryString);
$identity = $user->identity();
# make sure the submitted identity belongs to the logged in user.
if (!$identity || $identity->identity() != @$request['identity']) {
  # TODO redirect redirect_url with an error.
  throw new Exception('Unknown identity');
}
$raw_trust_root = @$request['trust_root'] ? $request['trust_root'] : @$request['return_to'];
$trust_root = OpenID_TrustRoot::findByTrustRoot($identity, $raw_trust_root);
if (!$trust_root)
    $trust_root = OpenID_TrustRoot::create($identity, $raw_trust_root);
$log->debug("trust_root={$trust_root->trustRoot()}");


# check if this is a postback.  There are two possible postbacks:
#  1. trust-* : these are responses to the authorization question.
#  2. edit-persona : the user wants to edit the selected persona.
#  3. new-persona : the user wants to create a new persona.

# First Postback type?  (1. trust-*)
$trust = null;
if (@$_POST['trust-allways'])
    $trust = 'allways';
elseif(@$_POST['trust-once'])
    $trust = 'once';
elseif (@$_POST['trust-deny'])
    $trust = 'deny';
# if its a postback, handle it.
if ($trust) {
    $log->debug("handling postback");

    $trust_root->approveCount($trust_root->approveCount()+1);
    switch ($trust) {
        case 'allways':
            $trust_root->autoApprove(true);
            $trust = true;
            break;
        case 'once':
            $trust = true;
            break;
        default:
            $trust_root->autoApprove(false);
            $trust = false;
    }

    # handle the persona change (if submited).
    $personaId = @$_POST['persona'];
    if ($personaId && $trust === true) {
        $persona = OpenID_Persona::findById($identity, $personaId);
        $trust_root->persona($persona);
    }

    $trust_root->save();
    $session = OpenID_UserSession::open();
    # set a session variable that will let OpenID_Provider known the
    # result of this Trust action.  Set it to expire in 5 minutes.
    # NB: OpenID_Provider will later delete this value from session.
    $session->set('trust.result', array(time()+5*60, $trust));
    $log->debug("Trust result $trust");

    $location = $redirect_url;
    $log->debug("Redirect to $location");

    if ($trust == 'yes') {
        header('HTTP/1.0 302 You\'re trusted my friend');
        header("Location: $location");
        header('Content-type: text/plain; charset=UTF-8');
        echo 'Dear Consumer, you earn my trust, take my beloved identity.';
    } else {
        header('HTTP/1.0 302 friend or foe?');
        header("Location: $location");
        header('Content-type: text/plain; charset=UTF-8');
        echo 'Foe!';
    }
    exit;
}

$persona_url = OpenID_Config::personaUrl();

# Second or third Postback type?  (2. edit-persona or 3. new-persona)
if (@$_POST['edit-persona'] || @$_POST['new-persona']) {
    $persona = @$_POST['persona'];
    if (!$persona || @$_POST['new-persona'])
        $persona = 0;
    $location = OpenID_QueryString::merge(
        $persona_url,
        array(
            'persona' => $persona,
            'redirect_url' => $redirect_url
        )
    );
    header('HTTP/1.0 302 Go on');
    header("Location: $location");
    header('Content-type: text/plain; charset=UTF-8');
    echo 'Go on...';
    exit;
}


$required = array();
if (@$request['sreg.required'])
    $required = explode(',', $request['sreg.required']);
$optional = array();
if (@$request['sreg.optional'])
    $optional = explode(',', $request['sreg.optional']);

$log->debug('SR: required='.@$request['sreg.required']);
$log->debug('SR: optional='.@$request['sreg.optional']);

$selectedPersona = $trust_root->persona();
$personas = null;
$personaAttributes = null;
if (count($optional) || count($required)) {
    $personas = array();
    $sortFieldByLabel = create_function(
        '$a,$b',
        'return strnatcasecmp($a[\'label\'], $b[\'label\']);'
    );
    $identityPersonas = $identity->personas();
    $identityPersonas = $identityPersonas ? $identityPersonas : array();
    foreach ($identityPersonas as $persona) {
        $fields = array();
        $requiredFieldsWithValue = 0;
        foreach ($required as $name) {
            $label = OpenID_UI_PersonaHelper::label($name);
            $value = $persona->get($name);
            if ($value !== null)
                ++$requiredFieldsWithValue;
            $fields[] = array(
                'required' => true,
                'name' => $name,
                'label' => $label,
                'value' => $value
            );
        }
        foreach ($optional as $name) {
            $label = OpenID_UI_PersonaHelper::label($name);
            $fields[] = array(
                'required' => false,
                'name' => $name,
                'label' => $label,
                'value' => $persona->get($name)
            );
        }

        # sort the fields by label.
        # XXX maybe we should sort them by "importance"?  or just leave
        #     with the order the consumer as specified the request?
        #     e.g.: nickname,fullname,email ?
        usort($fields, $sortFieldByLabel);

        $personas[$persona->id()] = array(
            'id' => $persona->id(),
            'name' => $persona->name(),
            'valid' => $requiredFieldsWithValue == count($required),
            'fields' => $fields
        );
        # if the user does not have a default persona, then for the form
        # on this page, we use the first persona.
        if (!$selectedPersona)
            $selectedPersona = $persona;
    }
    if (!count($personas))
        $personas = null;

    # build up the personaAttributes with the attributes the Consumer
    # wants to known about the user.
    $personaAttributes = array();
    foreach ($required as $name) {
        $label = OpenID_UI_PersonaHelper::label($name);
        $personaAttributes[] = array(
            'required' => true,
            'name' => $name,
            'label' => $label
        );
    }
    foreach ($optional as $name) {
        $label = OpenID_UI_PersonaHelper::label($name);
        $personaAttributes[] = array(
            'required' => false,
            'name' => $name,
            'label' => $label,
        );
    }
    # sort the fields by label.
    # XXX maybe we should sort them by "importance"?  or just leave
    #     with the order the consumer as specified the request?
    #     e.g.: nickname,fullname,email ?
    usort($personaAttributes, $sortFieldByLabel);
}

# fill page variables.
$page = array(
    # Page title.
    'title'             => 'Trust',
    # URL to the persona edit page.
    'persona_url'       => $persona_url,
    # URL where we should redirect to after we are done.
    'redirect_url'      => $redirect_url,
    # user identity.
    'identity'          => $identity->identity(),
    # Consumer URL trust root.
    'trust_root'        => $trust_root->trustRoot(),
    # user selected persona.
    'persona'           => $selectedPersona,
    # the persona attributes that the Consumer wants to known.
    'personaAttributes' => $personaAttributes,
    # user personas.
    'personas'          => $personas,
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php echo h($page['title']); ?> -- Identity Provider</title>
        <style type="text/css">@import "s/p-trust.css?2";</style>
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

            function selectPersona(id) {
            	var data = personas[id];
                if (!data)
                    return;
                var personaFields = document.getElementById('persona-fields');

                // find the table, and erase all child elements.
                var tables = personaFields.getElementsByTagName('tbody');
                var tbody = tables[0];
                while (tbody.firstChild)
                    tbody.removeChild(tbody.firstChild);
                // NB: "table.innerHTML = '';" does not work on IE 7.

                // create the DOM nodes for representing the persona
                // attributes.
                var fields = data.fields;
                for (var i = 0; i < fields.length; ++i) {
                    var f = fields[i];
                    var required = f['required'];
                    var label = f['label'] + ':';
                    var value = f['value'];
                    var tr = document.createElement('tr');
                    tr.className = required ? 'required' : 'optional';
                        var th = document.createElement('th');
                        th.innerHTML = HTML.escape(label).replace(' ', '&nbsp;');
                        tr.appendChild(th);
                        var td = document.createElement('td');
                        td.appendChild(document.createTextNode(value));
                        tr.appendChild(td);
                    tbody.appendChild(tr);
                }
            }

            window.onload = function() {
                var persona = document.getElementById('persona');
                if (!persona)
                    return;
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
    <body id="p-trust">
        <div id="header">
            <h1>Identity Provider</h1>
        </div>
        <div id="page">
            <div id="page-t">
                <div id="page-b">
                    <div id="page-content">
                        <h2 id="subtitle">Trust</h2>
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
                        <form action="trust.php" method="post">
                            <input type="hidden" name="redirect_url" value="<?php echo h($page['redirect_url']); ?>" />
                            <p>The site at <em><?php echo h($page['trust_root']); ?></em> is asking you to authenticate.</p>
<?php if (@$page['personas']) { ?>
                            <p>Its also asking for some information about You.  Select the persona you want to use:</p>
                            <fieldset id="persona-fields">
                                <legend>
                                    <select id="persona" name="persona">
<?php   foreach ($page['personas'] as $id => $persona) { ?>
                                        <option value="<?php echo h($id); ?>"<?php if (@$page['persona'] && $page['persona']->id() == $id) echo ' selected="selected"'; ?><?php if (!$persona['valid']) echo ' class="invalid"'; ?>>
                                            <?php echo h($persona['name']); ?>
                                        </option>
<?php   } ?>
                                    </select>
                                    <input type="submit" id="edit-persona" name="edit-persona" value="Edit" title="Edit this Persona before deciding" />
                                    <input type="submit" id="new-persona" name="new-persona" value="Create New" title="Create a New Persona" />
                                </legend>
                                <table>
                                    <tbody>
<?php   if (@$page['persona']) { ?>
<?php       foreach ($page['personas'][$page['persona']->id()]['fields'] as $f) {?>
                                        <tr class="<?php echo $f['required']?'required':'optional';?>">
                                            <th><?php echo str_replace(' ', '&nbsp;', h($f['label'])); ?>:</th>
                                            <td><?php echo h($f['value']); ?></td>
                                        </tr>
<?php       } ?>
<?php   } ?>
                                    </tbody>
                                </table>
                            </fieldset>
<?php } elseif ($page['personaAttributes']) { ?>
                            <p>Its also asking for some information about you:</p>
                            <ul id="persona-fields">
<?php   foreach ($page['personaAttributes'] as $attribute) { ?>
                                <li class="<?php echo $attribute['required'] ? 'required': 'optional' ?>"><?php echo h($attribute['label']); ?></li>
<?php } ?>
                            </ul>
                            <p>Altough, you need to <input type="submit" id="new-persona" name="new-persona" value="Create New Persona" title="Create a New Persona" /> first.</p>
<?php } ?>

                            <p id="question">Do you want to authenticate?</p>
                            <p>
                                <input type="submit" id="trust-allways" name="trust-allways" value="Yes, allways" />
                                <input type="submit" id="trust-once" name="trust-once" value="Yes, but only this time" />
                                <input type="submit" id="trust-deny" name="trust-deny" value="No" />
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
