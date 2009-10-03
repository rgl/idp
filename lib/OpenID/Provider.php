<?php
/**
 * OpenID Association classes.
 *
 * @package OpenID
 */
/***/

require_once('DiffieHellman.php');
require_once('Decimal.php');
require_once('OpenID/QueryString.php');
require_once('OpenID/KeyValue.php');
require_once('OpenID/MAC.php');
require_once('OpenID/Association.php');
require_once('OpenID/Identifier.php');
require_once('OpenID/TrustRoot.php');
require_once('OpenID/UserSession.php');
require_once('OpenID/Helper.php');

/**
 * Represents an OpenID Identity Provider Backend.
 *
 * This implements the Backend of the OpenID 1.1 protocol and the
 * Simple Registration 1.0 protocol.
 *
 * When this needs to comunicate with the user, an HTTP redirect will be
 * used to the following Endpoints (as configured in OpenID_Config):
 *
 * <pre>
 * idp.provider.url
 *   URL to the this Provider page.
 *
 * idp.identity.url
 *   URL to the Identity page.
 *
 * idp.login.url
 *   URL to the Login page.
 *
 * idp.trust.url
 *   URL to the Trust page.
 *
 * idp.persona.url
 *   URL to the Persona edit page.
 * </pre>
 *
 * The HTTP redirect will contain the single query string argument
 * "redirect_url" which contains the URL to where the Endpoint should
 * redirect after the user as answered the needed questions.
 *
 * The answers will be collected by this class through the
 * OpenID_UserSession class.
 *
 * Currently, only the Trust Endpoint needs to answer with:
 *
 * <code>
 * $result = array(
 *   time() + 5*60, # int $expiresAt
 *   true           # boolean $trusts
 * );
 * # int $expiresAt  after this UNIX timestamp the result is expired,
 *                   and will not be used 
 * # boolean $trusts true to indicate the user wants to authenticate
 *
 * $session = OpenID_UserSession::open();
 * $session->set('trust.result', $result);
 * </code>
 *
 * NB: This class will delete the 'trust.result' from the UserSession.
 *
 * @package OpenID
 */
class OpenID_Provider
{
    /**
     * Handles the OpenID request using the data from the PHP environment.
     *
     * NB: This only returns iif the current request is *not* an OpenID request.
     */
    public function handleRequest()
    {
        $log = OpenID_Config::logger();
        try {
            $all = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;

            # NB: O ponto (".") no nome das variaveis é substituido por "_"!
            #     Foi herdado dos tempos em que o register_globals era senhor e mestre.

            # is this an OpenID request?
            if (isset($all['openid_mode'])) {
                $request = OpenID_Helper::parametersFromRequest(OpenID_QueryString::decodeEnvironment());
                list($status, $headers, $body) = $this->handleOpenIDRequest($request, $_SERVER['REQUEST_METHOD']);
                header("HTTP/1.0 $status");
                foreach ($headers as $header)
                    header($header);
                echo $body;
                $body = preg_replace('/^/m', '  ', $body);
                $log->debug("response:\n$body");
                die;
            } else {
                # this is a normal HTTP request (non-openid).
                return;
            }
        } catch (IllegalArgumentException $e) {
            $log->fatal('Protocol error', $e);
            if (!headers_sent()) {
                header('HTTP/1.0 400 Bad Request');
                header('Content-Type: text/plain; charset=UTF-8');
                echo "You sent me junk... you spammer!\n";
                # only unleash on development.
                if (OpenID_Config::profile() == 'development') {
                    $text = "Cause:\n"
                        . preg_replace('/^/m', '  ', $e->getMessage())
                        . "\n\n"
                        . "Stack trace:\n"
                        . preg_replace('/^/m', '  ', $e->getTraceAsString())
                        ;
                    echo "\n\n$text\n\n";
                }
                die('Carpe diem.');
            }
            # don't bother sending another text, or else we will fubar the
            # (incomplete) response.
            die;
        } catch (Exception $e) {
            $log->fatal('Oops', $e);
            if (!headers_sent()) {
              header('HTTP/1.0 500 Internal Server Error');
                header('Content-Type: text/plain; charset=UTF-8');
                echo "Oh oh... my underpants are wet now...\n";
                # only unleash on development.
                if (OpenID_Config::profile() == 'development') {
                    $text = "Cause:\n"
                        . preg_replace('/^/m', '  ', $e->getMessage())
                        . "\n\n"
                        . "Stack trace:\n"
                        . preg_replace('/^/m', '  ', $e->getTraceAsString())
                        ;
                    echo "\n\n$text\n\n";
                }
                die('Carpe diem.');
            }
            # don't bother sending another text, or else we will fubar the
            # (incomplete) response.
            die;
        }
    }

    /**
     * Handles an Open ID request.
     *
     * Returns an array with three items:
     *   * HTTP Status Code (optional;  defaults to '200 OK').
     *   * Array of Array Pair with the HTTP headers.
     *   * HTTP body.
     *
     * OR it throws an Exception when something bad happened.
     */
    private function handleOpenIDRequest($request, $httpMethod)
    {
        $mode = @$request['mode'];
        switch ($mode) {
            case 'associate':
                return $this->handleAssociate($request, $httpMethod);

            case 'checkid_immediate':
                return $this->handleCheckidImmediate($request, $httpMethod);

            case 'checkid_setup':
                return $this->handleCheckidSetup($request, $httpMethod);

            case 'check_authentication':
                return $this->handleCheckAuthentication($request, $httpMethod);

            default:
                throw new IllegalArgumentException('mode unknown');
        }
    }

    private function handleAssociate($request, $httpMethod)
    {
        # Description:
        #   Establish a shared secret between a Smart Consumer and this
        #   Identity Provider.
        #   This shared secret will be used as a HMAC key in future
        #   identity check requests.
        # HTTP method:
        #   POST
        # Flow:
        #   Consumer -> IdP -> Consumer

        if ($httpMethod != 'POST')
            throw new IllegalArgumentException('HTTP_REQUEST_METHOD You must use a POST request');

        # Estabelece um segredo partilhado entre o Consumer e este Provider

        $response = array();

        # Opcional.  Um de: 'HMAC-SHA1' (omissão).
        $assoc_type = @$request['assoc_type'];
        if ($assoc_type === null) $assoc_type = 'HMAC-SHA1';
        # Opcional.  Um de: '' (omissão; cleartext), 'DH-SHA1' (recomendado).
        $session_type = @$request['session_type'];
        if ($session_type === null) $session_type = '';
        switch ($session_type) {
            case 'DH-SHA1':
                # Opcional.  Um de: base64(btwoc(g))
                # NB: Deve existir sse o DH Modulus tb existir.
                $dh_gen = Decimal::fromBase64(@$request['dh_gen'], OpenID_Association::Default_DH_Gen);
                # Opcional.  base64(btwoc(p))
                $dh_modulus = Decimal::fromBase64(@$request['dh_modulus'], OpenID_Association::Default_DH_Modulus);
                # base64(btwoc(g ^ x mod p))
                $dh_consumer_public = Decimal::fromBase64(@$request['dh_consumer_public']);

                if (!preg_match('/^HMAC-([A-Z\\d]+)$/', $assoc_type, $matches))
                    throw new IllegalArgumentException('Unknown association type');
                $assoc_type_hash = $matches[1];
                if ('SHA1' != $assoc_type_hash)
                    throw new IllegalArgumentException('Hash michmatch between association and session type');

                # create an association handle/id.
                $association = OpenID_Association::create($assoc_type);
                # Compute keys through Diffie-Hellman
                list($dh_server_public, $dh_server_private) = DiffieHellman::generateKeys($dh_gen, $dh_modulus);
                $zz = DiffieHellman::sharedSecret($dh_consumer_public, $dh_server_private, $dh_modulus);
                # Compute the association shared secret, and encrypt it
                # using the DH shared secret.
                # TODO what did we gain in encrypting the mac key?
                $enc_mac_key = Base64::encode(OpenID_MAC::run($assoc_type_hash, Decimal::toBinary($zz), $association->macKey()));

                # TODO save (assoc_handle, mac_key, expires_at) in a data store.
                #      expires_at is time() + lifetime

                $response['assoc_type']       = $assoc_type;
                $response['assoc_handle']     = $association->id();
                $response['expires_in']       = $association->defaultLifetime();
                $response['session_type']     = $session_type;
                $response['dh_server_public'] = Decimal::toBase64($dh_server_public);
                $response['enc_mac_key']      = $enc_mac_key;

                return array(
                    '200 OK',
                    array('Content-type: text/plain; charset=UTF-8'),
                    OpenID_KeyValue::encode($response)
                );

            # cleartext
            case '':
                # TODO make this mode tunable (thou, this can be done using a pre-filter)
                #      maye only allow when using HTTPS?
                $association = OpenID_Association::create($assoc_type);
                $mac_key = Base64::encode($association->macKey());
                $response['assoc_type'] = $assoc_type;
                $response['assoc_handle'] = $association->id();
                $response['expires_in'] = $association->defaultLifetime();
                $response['mac_key'] = $mac_key;

                return array(
                    '200 OK',
                    array('Content-type: text/plain; charset=UTF-8'),
                    OpenID_KeyValue::encode($response)
                );

            default:
                throw new Exception('unknown session type');
        }
    }

    private function handleCheckidImmediate($request, $httpMethod)
    {
        # Description:
        #   Ask an Identity Provider if a End User owns the Claimed
        #   Identifier, getting back an immediate "yes" or "can't say"
        #   answer.
        # HTTP method:
        #   GET
        # Flow:
        #   Consumer -> User-Agent -> IdP -> User-Agent -> Consumer
        #   NB: There is no interaction with the End-User.
        #   NB: This is an indirect request (Indirect Communication).

        # = Request arguments
        #
        # openid.identity
        #     Value: Claimed Identifier
        # openid.assoc_handle
        #     Value: The assoc_handle from the associate request.
        #     Note:  Optional; Consumer MUST use check_authentication if
        #            an association handle isn't provided or the
        #            Identity Provider feels it is invalid.
        #     Note:  If no association handle is sent, the transaction
        #            will take place in Stateless Mode (Verifying
        #            Directly with the OpenID Provider).
        # openid.return_to
        #     Value: URL where the Provider SHOULD return the User-Agent
        #     back to.
        # openid.trust_root
        #     Value:   URL the Provider SHALL ask the End User to trust.
        #     Default: return_to URL
        #     Note:    Optional; the URL which the End User SHALL
        #              actually see to approve.
        #
        # = Response arguments
        # == Sent on Failed Assertion
        # openid.user_setup_url
        #   Value: URL to redirect User-Agent to so the End User can do
        #          whatever's necessary to fulfill the assertion.

        if ($httpMethod != 'GET')
            throw new IllegalArgumentException('HTTP_REQUEST_METHOD You must use a GET request');

        # Claimed Identifier.
        $identity = OpenID_Identifier::findByIdentity(@$request['identity']);
        if (!$identity || $identity->disabled())
            throw new IllegalArgumentException('identity is unkown');

        # TODO screen identity to make sure its one managed by us.
        # Optional.  The assoc_handle from the associate request.
        $assoc_handle = @$request['assoc_handle'];
        # URL where the Provider SHOULD return the User-Agent back to.
        $return_to = OpenID_TrustRoot::validate(@$request['return_to']);
        # TODO screen return_to!
        # Optional.  URL the Provider SHALL ask the End User to trust.
        $trust_root = @$request['trust_root'];
        if (!$trust_root)
            $trust_root = $return_to;
        $trust_root = OpenID_TrustRoot::validate($trust_root);

        # TODO screen trust_root!  which is the Consumer address.
        # TODO return_to URL MUST descend from the openid.trust_root

        # only accept it iff the user is trusting the consumer.
        $isTrusted = $identity->trusts($trust_root);
        if ($isTrusted) {
            $session = OpenID_UserSession::open();
        } else {
            $session = null;
        }

        if (!$isTrusted || !$session) {
            # the user didn't trust the consumer.
            # OR
          # no user session.  we can't create one either because
            # we are on immediate mode, so just bail and say nay.
            $response = array();
            $response['mode'] = 'id_res';

            # TODO retreive the IdP URL from the profile.
            # TODO when is the end-user going to see this?
            # the user_setup_url has the same parameters as the request,
            # but the mode changed to "checkid_setup".
            # XXX probably we should copy the array, and modify the copy.
            $request['mode'] = 'checkid_setup';
            $request = OpenID_Helper::prefixParameters($request);
            $user_setup_url = OpenID_Config::selfUrl(null, $request);
            $response['user_setup_url'] = $user_setup_url;

            $result = OpenID_Helper::prefixParameters($response);
            $return_to = OpenID_QueryString::merge($return_to, $result);

            # Redirect the User Agent.  We can use one of the following HTTP
            # redirect status code:
            #   * 302 Found
            #   * 303 See Other
            #   * 307 Temporary Redirect
            # See http://tools.ietf.org/html/rfc2616#section-10.3
            # NB: Make sure the proxy does not cache these responses.
            return array(
                '303 Nay!',
                array(
                    'Content-type: text/plain; charset=UTF-8',
                    "Location: $return_to"
                ),
                'Not so fast dude.  You need to pass at customs first!'
            );
        }

        if ($assoc_handle) {
            $association = OpenID_Association::open($assoc_handle);
            if (!$association) {
                $response['invalidate_handle'] = $assoc_handle;
                $association = OpenID_Association::create('HMAC-SHA1', true);
            }
        } else {
            $association = OpenID_Association::create('HMAC-SHA1', true);
        }

        $this->simpleRegistration($identity, $trust_root, $association, $request, $response);
        $response['mode'] = 'id_res';
        $response['identity'] = $identity->identity();
        $response['return_to'] = $return_to;
        $response['assoc_handle'] = $association->id();
        # sign the response.
        # NB: At least, identity and return_to MUST be signed.
        if (@$response['signed'])
            $response['signed'] .= ',';
        else
            $response['signed'] = '';
        $response['signed'] .= 'mode,identity,return_to';
        $signature = $association->sign($response['signed'], $response);
        $response['sig'] = Base64::encode($signature);

        $result = OpenID_Helper::prefixParameters($response);
        $return_to = OpenID_QueryString::merge($return_to, $result);

        # Redirect the User Agent.  We can use one of the following HTTP
        # redirect status code:
        #   * 302 Found
        #   * 303 See Other
        #   * 307 Temporary Redirect
        # See http://tools.ietf.org/html/rfc2616#section-10.3
        # NB: Make sure the proxy does not cache these responses.
        return array(
            '302 Go on my friend...',
            array(
                'Content-type: text/plain; charset=UTF-8',
                "Location: $return_to"
            ),
            'Persue your destiny, my grashoper friend!'
        );
    }

    private function handleCheckidSetup($request, $httpMethod)
    {
        $log = OpenID_Config::logger();
        # Claimed Identifier.
        $identity = OpenID_Identifier::findByIdentity(@$request['identity']);
        # Optional.  The assoc_handle from the associate request.
        # If this is not present, then this is a stateless setup.
        $assoc_handle = @$request['assoc_handle'];
        # URL where the Provider SHOULD return the User-Agent back to.
        $return_to = @$request['return_to'];
        # TODO screen return_to!
        # Optional.  URL the Provider SHALL ask the End User to trust.
        $trust_root = @$request['trust_root'];
        if (!$trust_root)
            $trust_root = $return_to;
        $trust_root = OpenID_TrustRoot::validate($trust_root);

        if (!$identity)
            throw new IllegalArgumentException('Invalid Identifier');
        #if ($trust_root != 'http://consumer.example/')
        #    throw new IllegalArgumentException('Untrusted Consumer');
        # TODO screen trust_root to make sure its allowed.

        # if the user is not yet authenticated, redirect him to the
        # login page.  Which MUST redirect back to this same URL
        # after the user if logged in.
        $session = OpenID_UserSession::open();
        if (!$session) {
            $redirect_url = OpenID_Config::selfUrl().'?'.$_SERVER['QUERY_STRING'];
            $location = OpenID_Config::loginUrl(
                array('redirect_url' => $redirect_url)
            );
            $log->info('Redirecting to Login page using: '.$location);
            $log->debug('done...');
            return array(
                '302 Who are you?',
                array(
                    'Content-type: text/plain; charset=UTF-8',
                    "Location: $location"
                ),
                'I don\'t talk to strangers!'
            );
        }

        # see if the user trusts trust_root.  If he doesn't, cancel
        # the request.
        $isTrusted = $identity->trusts($trust_root);
        if (!$isTrusted) {
            # collect the (possible) trust page result from the session.
            $trust = false;
            $trustResult = $session->get('trust.result');
            if ($trustResult) {
                # OK, we have a trust response, handle it.
                list($expiresAt, $trust) = $trustResult;
                $session->delete('trust.result');
                # only accept the result if it didn't expire.
                if (time() >= $expiresAt)
                    $trust = false;
            } else {
                # we do not have a trust response, so redirect the
                # user-agent to the trust page.
                $redirect_url = OpenID_Config::selfUrl().'?'.$_SERVER['QUERY_STRING'];
                $location = OpenID_Config::trustUrl(
                    array('redirect_url' => $redirect_url)
                );
                $log->info('Redirecting to Trust page using: '.$location);
                $log->debug('done...');
                return array(
                    '302 Lemme ask my master if I trust you',
                    array(
                        'Content-type: text/plain; charset=UTF-8',
                        "Location: $location"
                    ),
                    'Hang on!'
                );
            }
            # the user trusts?
            if (!$trust) {
                # no.  so, cancel.
                $location = OpenID_QueryString::merge(
                    $return_to,
                    array('openid.mode' => 'cancel')
                );
                $log->info('Canceling untrusted setup: '.$location);
                $log->debug('done...');
                return array(
                    '302 I do not trust you',
                    array(
                        'Content-type: text/plain; charset=UTF-8',
                        "Location: $location"
                    ),
                    'You do not earn my trust!'
                );
                break;
            }
            # the user trusts, so continue.
        }

        # if we reach here, then, the user trusts the consumer, so
        # send a complete response.

        $invalidate_handle = null;
        $association = null;
        # stateful association.
        if ($assoc_handle) {
            $association = OpenID_Association::open($assoc_handle);
            # if the association cannot be opened, we need to tell
            # the comsumer to invalidade it.  Bellow, we also
            # fallback to a new stateless association.
            if (!$association)
                $invalidate_handle = $assoc_handle;
        }
        # stateless association (or fallback from invalid statefull
        # association)
        if (!$association) {
            # TODO get the stateless association type from the profile.
            $association = OpenID_Association::create('HMAC-SHA1', true);
        }

        $response = array();
        $this->simpleRegistration($identity, $trust_root, $association, $request, $response);
        if ($invalidate_handle)
            $response['invalidate_handle'] = $invalidate_handle;
        $response['mode'] = 'id_res';
        $response['identity'] = $identity->identity();
        $response['return_to'] = $return_to;
        $response['assoc_handle'] = $association->id();
        # sign the response.
        # NB: At least, identity and return_to MUST be signed.
        if (@$response['signed'])
            $response['signed'] .= ',';
        else
            $response['signed'] = '';
        $response['signed'] .= 'identity,return_to';
        $signature = $association->sign($response['signed'], $response);
        $response['sig'] = Base64::encode($signature);
        # prefix response with "openid.".
        $result = OpenID_Helper::prefixParameters($response);
        $return_to = OpenID_QueryString::merge($return_to, $result);

        # Redirect the User Agent.  We can use one of the following HTTP
        # redirect status code:
        #   * 302 Found
        #   * 303 See Other
        #   * 307 Temporary Redirect
        # See http://tools.ietf.org/html/rfc2616#section-10.3
        # NB: Make sure the proxy does not cache these responses.
        $log->info('Redirecting to Consumer using: '.$return_to);
        $log->debug('done...');
        return array(
            '302 Go on my friend...',
            array(
                'Content-type: text/plain; charset=UTF-8',
                "Location: $return_to"
            ),
            'Persue your destiny, my grashoper friend!'
        );
    }

    private function handleCheckAuthentication($request, $httpMethod)
    {
        if ($httpMethod != 'POST')
            throw new IllegalArgumentException('HTTP_REQUEST_METHOD You must use a POST request');

        # The assoc_handle from the checkid_setup or
        # checkid_immediate response.
        $assoc_handle = @$request['assoc_handle'];
        $signed = @$request['signed'];
        $signature = Base64::decode(@$request['sig']);
        # Optional.
        $invalidate_handle = @$request['invalidate_handle'];
        # see if we need to check the invalidate_handle.
        # if its still valid, don't tell the Consumer to invalidate
        # it.
        if ($invalidate_handle) {
            # NB: This handle came from a failed (stateful) checkid_*.
          $association = OpenID_Association::open($invalidate_handle);
            if ($association)
                $invalidate_handle = null;
        }

        # We MUST make sure that we use an stateless association
        # key AND NOT a statefull one.
        # NB: if the assoc_handle is not stateless, this will throw.
        $association = OpenID_Association::open($assoc_handle, true);
        # to verify the signature, we temporaly change the mode to
        # id_res (it was the mode used to generate the original
        # signature).
        $request['mode'] = 'id_res';
        # verify the signature.
        $is_valid = $association->verify($signed, $request, $signature);
        # revert the mode back.
        $request['mode'] = 'check_authentication';

        $response['is_valid'] = $is_valid ? 'true' : 'false';
        if ($invalidate_handle)
            $response['invalidate_handle'] = $invalidate_handle;

        return array(
            '200 OK',
            array('Content-type: text/plain; charset=UTF-8'),
            OpenID_KeyValue::encode($response)
        );
    }

    /**
     * Appends the requested persona fields associated with $identity and
     * with $trust_root.
     */
    private function simpleRegistration($identity, $trust_root, $association, $request, &$response)
    {
        $fields = array();
        if (@$request['sreg.required'])
            $fields += explode(',', $request['sreg.required']);
        if (@$request['sreg.optional'])
            $fields += explode(',', $request['sreg.optional']);
        if (!count($fields))
            return;

        $log = OpenID_Config::logger();

        $log->debug('SR: request fields: '.implode(',', $fields));

        $trustRoot = $identity->trustRoot($trust_root);
        if (!$trustRoot) {
            $log->debug('SR: no trust root '.$trust_root.' information for identity '.$identity->identity());
            return;
        }
        $persona = $trustRoot->persona();
        if (!$persona) {
            $log->debug('SR: no persona associated with trust root '.$trust_root.' for identity '.$identity->identity());
            return;
        }

        # retreive all fields data.
        $data = array();
        foreach ($fields as $key) {
            $value = $persona->get($key);
            if ($value === null)
                continue;
            $data[$key] = $value;
        }

        # append the fields into the response.
        $signedFields = implode(',', array_map(
            create_function('$s', 'return "sreg.$s";'),
            array_keys($data)
        ));
        $log->debug('SR: signed fields: '.$signedFields);
        if (@$response['signed'])
            $response['signed'] .= ',';
        else
            $response['signed'] = '';
        $response['signed'] .= $signedFields;
        foreach ($data as $key => $value) {
          $response["sreg.$key"] = $value;
        }
    }
}
?>
