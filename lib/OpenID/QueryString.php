<?php
/**
 * Query String manipulation classes.
 *
 * @package OpenID
 */
/***/

require_once('IllegalArgumentException.php');

/**
 * Encodes and Decodes an HTTP query string (or an HTTP POSTed with the
 * mime type www/www-form-data) as described by the "query" syntax
 * component of RFC3986.
 *
 * This implementation parses the RAW data without the transformations
 * done by PHP:
 *
 *  - "Dots in incoming variable names" inide the "Variables from
 *    outside PHP" section on the PHP manual.  This is of particular
 *    concern to openid, which uses dots to separate the individual
 *    variable namespace.
 *  - automatically handling arrays.  See an example on the
 *    http_build_query function inide the PHP Manual.
 *  - Magic Quotes inside the values.
 *
 * NB: This class throws IllegalArgumentException when it detects
 *     Duplicate keys.
 *
 * @link http://tools.ietf.org/html/rfc2616  Hypertext Transfer Protocol – HTTP/1.1
 * @link http://tools.ietf.org/html/rfc3986  Uniform Resource Identifier (URI): Generic Syntax
 * @link http://www.w3.org/TR/uri-clarification/  URIs, URLs, and URNs: Clarifications and Recommendations 1.0
 * @package OpenID
 */
class OpenID_QueryString
{
    /**
     * Encode an Hash of string key value pairs into a query string.
     *
     * <code>
     * encode(array('a'=>'a', 'b'=>'b'))
     * # => a=a&b=b
     * </code>
     *
     * @param hash $data data to encode
     * @return string
     */
	static function encode($data)
    {
        if (!$data)
            return '';
        foreach ($data as $k => $v) {
        	if (!is_string($k) || !(is_string($v) || is_int($v)))
                throw new IllegalArgumentException('data MUST be an hash of strings.');
        }
        return http_build_query($data, '', '&');
    }

    /**
     * Does a SIMPLE merge of $url to $data.
     *
     * This is equivalent of doing:
     *
     * <code>
     *   $merged = $url.'?'.encode($data)
     * </code>
     *
     * When '?' is not already present on $url, OR if its alread
     * present, this is equivalent of:
     *
     * <code>
     *   $merged = $url.'&'.encode($data)
     * </code>
     *
     * @param string $url base URL
     * @param hash $data quetry string parameters to append into $url
     */
    static function merge($url, $data=null)
    {
        if (!$data)
            return $url;
        $qs = self::encode($data);
        if (strpos($url, '?') !== false)
            $merged = "$url&$qs";
        else
            $merged = "$url?$qs";
        return $merged;
    }

    /**
     * Decodes a query string into a hash.
     *
     * @param string $qs query string
     * @return hash query string data
     */
    static function decode($qs)
    {
        # RFC3986 defines a query generically as:
        #
        # query         = *( pchar / "/" / "?" )
        # pchar         = unreserved / pct-encoded / sub-delims / ":" / "@"
        # unreserved    = ALPHA / DIGIT / "-" / "." / "_" / "~"
        # pct-encoded   = "%" HEXDIG HEXDIG
        # sub-delims    = "!" / "$" / "&" / "'" / "(" / ")"
        #                 / "*" / "+" / "," / ";" / "="
        #
        # While RFC2616 Section 3.2.2 defines the HTTP URI semantics.
        #
        # Though, neither of these specify the exact semantics.  These
        # are described on the HTML specification, more exactly on the
        # definition of the "application/x-www-form-urlencoded" mime
        # type at:
        #   http://www.w3.org/TR/html401/interact/forms.html#h-17.13.4.1
        # Where its defined as:
        #
        # """
        # * Control names and values are escaped.  Space characters are
        #   replaced by `+', and then reserved characters are escaped as
        #   described in [RFC1738], section 2.2: Non-alphanumeric
        #   characters are replaced by `%HH', a percent sign and two
        #   hexadecimal digits representing the ASCII code of the
        #   character.  Line breaks are represented as "CR LF" pairs
        #   (i.e., `%0D%0A').
        # * The control names/values are listed in the order they appear
        #   in the document.  The name is separated from the value by
        #   `=' and name/value pairs are separated from each other by `&
        # """
        #
        # this basically means, we should split the key=value pairs at
        # "&" chars, and then split each one at "=", then urldecode key
        # and value.
        #
        # Besides this, we do not allow (after URL decoding) control
        # characters besides WHITESPACES (HORIZONTAL TAB, NEW LINES).
        # AND we also do not allow repeated keys.

        $data = array();
        if (!$qs)
            return $data;

        $pairs = explode('&', $qs);
        foreach ($pairs as $pair) {
        	$kv = array_map('urldecode', explode('=', $pair, 2));
            $k = $kv[0];
            $v = @$kv[1];
            if (!preg_match('/^[\x21-\xff]+$/', $k))
                throw new IllegalArgumentException('key with bad data');
            if ($v && !preg_match('/^[\x20-\xff\n\r\t]+$/', $v))
                throw new IllegalArgumentException('value with bad data');
            if (array_key_exists($k, $data))
                throw new IllegalArgumentException('key already exists');
            $data[$k] = $v;
        }

        return $data;
    }

    /**
     * Same has decode() but this will first extract the query string
     * from the given URI.
     *
     * @param string $uri the URI to decode
     * @return hash query string data
     */
    static function decodeURI($uri)
    {
    	return self::decode(parse_url($uri, PHP_URL_QUERY));
    }

    /**
     * This will decode the QueryString from RAW PHP environment
     * (bypasses $_GET and $_POST superglobals).
     *
     * This is needed because of the PHP herditage of the old
     * "register_globals on" days, all outside variables
     * (eg: $_GET) have the "." character replaced with "_".
     * this is not a big deal with OpenID 1, but with OpenID 2
     * it will be awakard to work arround... so we parse the
     * argments ourselfs from the RAW data.
     *
     * @param string $data if not null, we parse it instead of accessing PHP data. (a non null argument is used by the testing code).
     * @return hash query string parameters
     * @exception IllegalArgumentException when the data cannot be successfully extract from PHP environment or its invalid.
     */
    static function decodeEnvironment($data=null)
    {
        if (!$data) {
            switch ($_SERVER['REQUEST_METHOD']) {
            	case 'GET':
                    $data = $_SERVER['QUERY_STRING'];
                    break;
                case 'POST':
                    if ($_SERVER['CONTENT_TYPE'] != 'application/x-www-form-urlencoded')
                        throw new IllegalArgumentException('unsupported content-type');
                    $data = file_get_contents('php://input');
                    break;
                default:
                    throw new IllegalArgumentException('failed to decode environment');
            }
        }
        return self::decode($data);
    }
}
?>
