<?php
require_once('global.php');
require_once('DomainName.php');

class DomainNameTest extends MyTestCase
{
    var $goodFixtures = array(
        'example.com',
        'A.example.com',
        'a.example.com',
        'a.b.example.com',
        'a.b.c.example.com',
        'a.b-c.d.example.com',
        'a.b--c.d.example.com',
        'a.b9c.d.example.com',
        '9a.example.com', # In RFC1035 a label can only start with a
                          # letter, but there are plenty of domain
                          # names that start with a digit...
        'xn--olmundo-iwa.example.com', # IDNA encoded.
        'xn--ol-nia.xn--estbem-rta.example.com', # IDNA encoded.
        '53.com', # Yeah, actually there is a domain like that!
        '1.0.0.127.in-addr.arpa',
    );

    var $badFixtures = array(
        '',
        ' ',
        null,
        '*.example.com',
        'a-.example.com',
        '.example.com',
        'example.com.',
        'example..com',
        '%00xample.com',
        '%2E.example.com', # 0x2E == '.'
    );

    function testGoodDomainNames()
    {
        foreach ($this->goodFixtures as $fixture) {
            try {
               DomainName::validate($fixture);
            } catch (Exception $e) {
                $this->fail("$fixture SHOULD be a valid domain, but DomainName::validate failed");
            }
        }
    }

    function testBadDomainNames()
    {
        foreach ($this->badFixtures as $fixture) {
            try {
               DomainName::validate($fixture);
               $this->fail("$fixture SHOULD NOT be a valid domain, but DomainName::validate did NOT fail");
            } catch (IllegalArgumentException $e) {
                # ignore because its expected.
            }
        }
    }

}
?>