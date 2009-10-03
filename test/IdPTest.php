<?php
require_once('global.php');
require_once('DiffieHellman.php');
require_once('Decimal.php');
require_once('Hash.php');
require_once('OpenID/QueryString.php');
require_once('OpenID/KeyValue.php');
require_once('OpenID/Association.php');
require_once('OpenID/Helper.php');

class IdPTest extends MyWebTestCase
{
    const DEFAULT_G = 'Ag==';
    const DEFAULT_P = 'ANz5OguIOXLsDhmYmsWizjEOHTdxfo2Vcbt2I3MYZuYe91ouJ4mLBX+YkcLiemOcPym2CBRYHNOyyjmG0mg3BVd9RcLn5S3IHHoXGHblzqdLFEi/368Ygo79JRnxTkXjgmY0rxlJ5bU1zIKaSDuKdiI+XUkKJX8Fvf8W8vsixYOr';

    # YA Consumer Public Key
    # XA Consumer Private Key
    # These were obtained using the from the commented testGenerate
    # method.
    # YA is a 309 digit number.
    const CONSUMER_YA = 'AMEYAyZgYkD0+F85QpltmGv+LRgEjTgOmUCKKKsEI5bZ8DN/wyN9xO2zMgWYI/v3BNOxWwVLMSVTNUwoBIp7RVGyuql6tEcZzyI5kcaplelGfMEV6idovvgcWtdUP4yPx0LGGfMR0pYRe0yc7ekOA+qD2svLZFD3cFexwkGyFuaM';
    # XA is a 100 digit number.
    const CONSUMER_XA = 'BLxBzl1Nwh/qQQlI0hEATbxINfbt/hsjqVNY17lCTvB6Z3Z72aLqiDu2';

    /*function testGenerate()
    {
    	list($ya, $xa) = DiffieHellman::generateKeys(self::DEFAULT_G, self::DEFAULT_P);
        $DEFAULT_YA = Decimal::toBase64($ya);
        $DEFAULT_XA = Decimal::toBase64($xa);
        echo 'G=', Decimal::toBase64('2'), "\n";
        echo 'P=', Decimal::toBase64('155172898181473697471232257763715539915724801966915404479707795314057629378541917580651227423698188993727816152646631438561595825688188889951272158842675419950341258706556549803580104870537681476726513255747040765857479291291572334510643245094715007229621094194349783925984760375594985848253359305585439638443'), "\n";
        echo '#ya='.strlen($ya)."\n";
        echo '#xa='.strlen($xa)."\n";
        echo "YA=$DEFAULT_YA\n";
        echo "XA=$DEFAULT_XA\n";
    }*/

    function testDHSHA1Association()
    {
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'openid.mode' => 'associate',
            # Optional.  Defaults to HMAC-SHA1.
            #'openid.assoc_type' => 'HMAC-SHA1',
            # Optional.  Defaults to blank (cleartext).
            'openid.session_type' => 'DH-SHA1',
            # Optional.  Defaults to DEFAULT_P.
            #'openid.dh_modulus' => DEFAULT_P,
            # Optional.  Defaults to DEFAULT_G.
            #'openid.dh_gen' => DEFAULT_G,
            'openid.dh_consumer_public' => self::CONSUMER_YA
        );
        $body = $this->post(OpenID_Config::providerUrl(), $parameters);
        $this->assertStatus(200);
        $this->assertContentType('text/plain; charset=UTF-8');

        $kv = OpenID_KeyValue::decode($body);
        $this->assertArrayHasKey('assoc_type', $kv);
        $this->assertEquals('HMAC-SHA1', $kv['assoc_type']);
        $this->assertArrayHasKey('assoc_handle', $kv);
        $this->assertArrayHasKey('expires_in', $kv);
        $this->assertArrayHasKey('session_type', $kv);
        $this->assertEquals('DH-SHA1', $kv['session_type']);
        $this->assertArrayHasKey('dh_server_public', $kv);
        $this->assertArrayHasKey('enc_mac_key', $kv);
        $this->assertArrayNotHasKey('mac_key', $kv);

        $enc_mac_key = Base64::decode($kv['enc_mac_key']);
        $this->assertEquals(
            Hash::outputSize('SHA1'),
            strlen($enc_mac_key)
        );
    }

    function testClearTextAssociation()
    {
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'openid.mode' => 'associate',
            # Optional.  Defaults to HMAC-SHA1.
            #'openid.assoc_type' => 'HMAC-SHA1',
            # Optional.  Defaults to blank (cleartext).
            #'openid.session_type' => '',
            # Optional.  Defaults to DEFAULT_P.
            #'openid.dh_modulus' => DEFAULT_P,
            # Optional.  Defaults to DEFAULT_G.
            #'openid.dh_gen' => DEFAULT_G,
            'openid.dh_consumer_public' => self::CONSUMER_YA
        );
        $body = $this->post(OpenID_Config::providerUrl(), $parameters);
        $this->assertStatus(200);
        $this->assertContentType('text/plain; charset=UTF-8');

        $kv = OpenID_KeyValue::decode($body);
        $this->assertArrayHasKey('assoc_type', $kv);
        $this->assertEquals('HMAC-SHA1', $kv['assoc_type']);
        $this->assertArrayHasKey('assoc_handle', $kv);
        $this->assertArrayHasKey('expires_in', $kv);
        $this->assertArrayNotHasKey('session_type', $kv);
        $this->assertArrayNotHasKey('enc_mac_key', $kv);
        $this->assertArrayHasKey('mac_key', $kv);

        $mac_key = Base64::decode($kv['mac_key']);
        $this->assertEquals(
            Hash::outputSize('SHA1'),
            strlen($mac_key)
        );
    }

    # this tests the whole shebang:
    #  1. associate
    #  2. checkid_setup (without begin authenticated)
    #  3. authenticate through login page
    #  4. checkid_setup (now authenticated, but not trusted)
    #  5. trust through the trust page
    #  6. checkid_setup (now authenticated AND trusted)
    function testCheckIDSetupWithAssociation()
    {
        $log = OpenID_Config::logger();
        $log->debug('Unit Test IdpTest.testCheckIDSetupWithAssociation');

        # act 1.  associate.
        $log->debug('1. associate');
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'openid.mode' => 'associate',
            'openid.dh_consumer_public' => self::CONSUMER_YA
        );
        $body = $this->post(OpenID_Config::providerUrl(), $parameters);
        $this->assertStatus(200);
        $this->assertContentType('text/plain; charset=UTF-8');
        $akv = OpenID_KeyValue::decode($body);
        $this->assertArrayHasKey('mac_key', $akv);
        $this->assertArrayHasKey('assoc_handle', $akv);
        $this->assertArrayHasKey('assoc_type', $akv);
        $mac_key = Base64::decode($akv['mac_key']);
        $assoc_handle = $akv['assoc_handle'];
        $assoc_type = $akv['assoc_type'];


        # act 2.  checkid_setup (without begin authenticated).
        # checkid_setup;  should redirect to the login page.
        $log->debug('2. checkid_setup (without begin authenticated)');
        $trust_root = 'http://trust-once.consumer.example/';
        $return_to = $trust_root.'login?nonce=1234';
        $setup_parameters = array(
            'X-APP_PROFILE'       => 'test',
            'openid.mode'         => 'checkid_setup',
            'openid.identity'     => OpenID_Config::identityUrl('test-alice'),
            'openid.assoc_handle' => $assoc_handle,
            'openid.return_to'    => $return_to,
            # optional.  defaults to return_to.
            'openid.trust_root'   => $trust_root
        );
        $this->get(OpenID_Config::providerUrl(), $setup_parameters);
        $this->assertStatus(302);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure cookies are NOT sent.
        $this->assertEquals(null, $this->header('set-cookie'));
        $location = $this->header('location');


        # act 3.  authenticate through login page.
        # send user credentials to the login page.  Which should
        # redirect back to the IdP page.
        # NB: This works, because we assume the IdP will ask right away
        #     the password.
        $log->debug('3. authenticate through login page');
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'user' => 'test-alice',
            'password' => 'password',
            'login' => 'Login'
        );
        $body = $this->post($location, $parameters);
        $this->assertStatus(302);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure cookies are sent.
        $this->assertNotSame(null, $this->header('set-cookie'));
        $location = $this->header('location');


        # act 4.  checkid_setup (now authenticated, but not trusted).
        # do the real checkid_setup (because we are now authenticated
        # BUT are not trusted).
        $log->debug('4. checkid_setup (now authenticated, but not trusted)');
        $body = $this->get($location);
        $this->assertStatus(302);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure cookies are NOT sent.
        $this->assertSame(null, $this->header('set-cookie'));
        $location = $this->header('location');


        # act 5.  trust through the trust page.
        # the consumer we used is not always trusted, so we are
        # redirected the Trust page.  Which we should POST to allow
        # "once".
        $log->debug('5. trust through the trust page');
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'trust-once' => 'trust-once',
        );
        $body = $this->post($location, $parameters);
        $this->assertStatus(302);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure cookies are NOT sent.
        $this->assertSame(null, $this->header('set-cookie'));
        $location = $this->header('location');
        $qs = OpenID_QueryString::decodeURI($location);
        # make sure we are not getting back the unsafe trust response.
        # this is now safetly communicated using the UserSession.
        $this->assertArrayNotHasKey('openid.X-IDP.trust', $qs);


        # act 6.  checkid_setup (now authenticated AND trusted).
        # do the real checkid_setup (because we are now authenticated
        # and trusted).
        $log->debug('6. checkid_setup (now authenticated AND trusted)');
        $body = $this->get($location);
        $this->assertStatus(302);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure cookies are NOT sent.
        $this->assertSame(null, $this->header('set-cookie'));
        $location = $this->header('location');
        $this->assertEquals($return_to, substr($location, 0, strlen($return_to)));
        $qs = OpenID_QueryString::decodeURI($location);
        $this->assertArrayHasKey('nonce', $qs);
        $this->assertArrayHasKey('openid.mode', $qs);
        $this->assertArrayHasKey('openid.assoc_handle', $qs);
        $this->assertArrayHasKey('openid.return_to', $qs);
        $this->assertArrayHasKey('openid.signed', $qs);
        $this->assertArrayHasKey('openid.sig', $qs);
        $this->assertArrayNotHasKey('openid.invalidate_handle', $qs);
        $this->assertEquals('1234', $qs['nonce']);
        $this->assertEquals('id_res', $qs['openid.mode']);
        $this->assertEquals($assoc_handle, $qs['openid.assoc_handle']);
        $this->assertEquals($setup_parameters['openid.return_to'], $qs['openid.return_to']);
        $association = new OpenID_Association(
            $assoc_handle,
            $assoc_type,
            HMAC::create('SHA1', $mac_key),
            false,
            time()+600000
        );
        $data = OpenID_Helper::parametersFromRequest($qs);
        $fields = $data['signed'];
        $signature = Base64::encode($association->sign($fields, $data));
        $this->assertEquals($signature, $data['sig']);
    }

    function testCheckIDImmediateWithoutEndUserLogin()
    {
        # do an association first.
        $parameters = array(
            'X-APP_PROFILE' => 'test',
            'openid.mode' => 'associate',
            'openid.dh_consumer_public' => self::CONSUMER_YA
        );
        $body = $this->post(OpenID_Config::providerUrl(), $parameters);
        $this->assertStatus(200);
        $this->assertContentType('text/plain; charset=UTF-8');
        $akv = OpenID_KeyValue::decode($body);
        $this->assertArrayHasKey('mac_key', $akv);
        $this->assertArrayHasKey('assoc_handle', $akv);

        # checkid_immediate;  should fail because we didn't yet
        # authenticate the end-user.
        $return_to = 'http://consumer.example/login';
        $parameters = array(
            'X-APP_PROFILE'       => 'test',
            'openid.mode'         => 'checkid_immediate',
            'openid.identity'     => OpenID_Config::identityUrl('test-alice'),
            'openid.assoc_handle' => $akv['assoc_handle'],
            'openid.return_to'    => $return_to
        );
        $body = $this->get(OpenID_Config::providerUrl(), $parameters);
        $this->assertStatus(303);
        $this->assertContentType('text/plain; charset=UTF-8');
        # make sure no cookies are sent.
        $this->assertSame(null, $this->header('set-cookie'));
        $location = $this->header('location');
        $this->assertEquals($return_to, substr($location, 0, strlen($return_to)));
        $qs = OpenID_QueryString::decodeURI($location);
        $this->assertArrayHasKey('openid.mode', $qs);
        $this->assertEquals('id_res', $qs['openid.mode']);
        $this->assertArrayHasKey('openid.user_setup_url', $qs);
    }

    # TODO test check_immediate with an untrusted root
    #      which should return "nay!" / "Setup Needed"

    function testCheckAuthentication()
    {
    	# TODO implement me!
    }
}
?>
