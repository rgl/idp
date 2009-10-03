<?php
$_REQUEST['X-APP_PROFILE'] = 'test';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
ini_set(
    'include_path',
    implode(
        PATH_SEPARATOR,
        array(
            realpath(dirname(__FILE__).'/../lib'),
            realpath(dirname(__FILE__).'/../vendor'),
            ini_get('include_path')
        )
    )
);
require_once(dirname(__FILE__).'/../config/config.php');

require_once('PHPUnit/Framework.php');
require_once('OpenID/QueryString.php');

# Use a application wide testcase class so we can modify it globablly if
# needed.
class MyTestCase extends PHPUnit_Framework_TestCase
{
}

class MyWebTestCase extends MyTestCase
{
    private $COOKIE_PATH;
    private $_http;

    function get($url, $parameters=null)
    {
        return $this->_http("GET", $url, $parameters);
    }

    function post($url, $parameters=null)
    {
        return $this->_http("POST", $url, $parameters);
    }

    function setUp()
    {
        parent::setUp();
        $this->COOKIE_PATH = dirname(__FILE__).DIRECTORY_SEPARATOR.'cookies.txt';
        @unlink($this->COOKIE_PATH);
    }

    private function _http($method, $url, $parameters)
    {
        # We use the cURL extension.
        # See the examples at http://curl.haxx.se/libcurl/php/

        # NB: If we need more features, we shoud use the HTTP extension
        #     available at http://pecl.php.net/package/pecl_http
        # NB: To send some file, we can use http_request_body_encode.

        $this->_http = array();
        $c = curl_init();
        try {
            $options = array(
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FORBID_REUSE    => true,
                CURLOPT_HEADER          => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_0,
                CURLOPT_COOKIEFILE      => $this->COOKIE_PATH,
                CURLOPT_COOKIEJAR       => $this->COOKIE_PATH,
            );
            switch ($method) {
                case 'POST':
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = OpenID_QueryString::encode($parameters);
                    break;

                case 'GET':
                    $options[CURLOPT_HTTPGET] = true;
                    $options[CURLOPT_URL] = OpenID_QueryString::merge($url, $parameters);
                    break;

                 default:
                    throw new IllegalArgumentException('Unknown HTTP method');
            }

            #curl_setopt_array($c, $options);
            foreach ($options as $option => $value) {
                if (!curl_setopt($c, $option, $value))
                    $this->fail('failed to set curl option.');
            }
            $body = curl_exec($c);
            if (curl_errno($c))
                $this->fail('failed to curl_exec: '.curl_error($c));
            list($headers, $body) = split("\r\n\r\n", $body, 2);
            $this->_http['status'] = intval(curl_getinfo($c, CURLINFO_HTTP_CODE));
            $this->_http['content_type'] = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
            $this->_http['headers'] = split("\r\n", $headers);
            array_shift($this->_http['headers']); # remove status line.
            curl_close($c);
            return $body;
        } catch (Exception $e) {
            curl_close($c);
            throw $e; # rethrow.
        }
    }

    function assertStatus($expectedStatuses)
    {
        if (!is_array($expectedStatuses))
            $expectedStatuses = array($expectedStatuses);
        $status = $this->_http['status'];
        $this->assertContains($status, $expectedStatuses);
        #if (!in_array($status, $expectedStatuses))
        #    $this->fail('Unexpected HTTP Status.  Expecting one of: '.implode(', ', $expectedStatuses)."; but got $status");
    }

    function header($header)
    {
        foreach ($this->_http['headers'] as $line) {
            list($name, $content) = split(':', $line, 2);
            if (strcasecmp($header, $name) != 0)
                continue;
            return trim($content);
        }
        return null;
    }

    function assertHeaderEquals($header, $expected)
    {
        $this->assertEquals($expected, $this->header($header));
    }

    function assertContentType($expectedContentTypes)
    {
        if (!is_array($expectedContentTypes))
            $expectedContentTypes = array($expectedContentTypes);
        $this->assertContains($this->_http['content_type'], $expectedContentTypes);
    }
}
?>
