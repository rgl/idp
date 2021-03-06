<?php
/**
 * Simple Registration support classes.
 *
 * @package OpenID.SR
 */
/***/

require_once('OpenID/SR/TimeZone.php');

/**
 * Represents a Simple Registration Country.
 *
 * A country in OpenID is represented using the two leter code from ISO3166.
 *
 * @link http://packages.debian.org/cgi-bin/search_packages.pl?searchon=names&version=all&exact=1&keywords=iso-codes iso-codes package 
 * @package OpenID.SR
 */
class OpenID_SR_Country
{
    private static $data;
    private $code;
    private $name;

    private function __construct($code, $name)
    {
        $this->code = $code;
        $this->name = $name;
    }

    /** @return string this country code */
    public function code()
    {
        return $this->code;
    }

    /** @return string this country name */
    public function name()
    {
        return $this->name;
    }

    /** @return array array of OpenID_SR_TimeZone with all the timezones associated with this country */
    public function timeZones()
    {
        return OpenID_SR_TimeZone::fromCountry($this);
    }

    /**
     * Returns the country that has the given code.
     *
     * @param string $code the country code to find
     * @return OpenID_SR_Country
     */
    public static function fromCode($code)
    {
        OpenID_SR_Country::load();
        if (!array_key_exists($code, OpenID_SR_Country::$data))
    	   return null;
        $entry = OpenID_SR_Country::$data[$code];
        return new OpenID_SR_Country($code, $entry['name']);
    }

    /** @return array array of OpenID_SR_Country with all the countries */
    public static function all()
    {
        OpenID_SR_Country::load();
        $all = array();
        foreach (OpenID_SR_Country::$data as $code => $keys) {
            $all[] = new OpenID_SR_Country($code, $keys['name']);
        }
        return $all;
    }

    private static function load()
    {
        if (OpenID_SR_Country::$data)
            return;
        require_once('OpenID/SR/data/AutoGeneratedCountry.php');
        OpenID_SR_Country::$data = OpenID_SR_data_AutoGeneratedCountry::$data;
    }
}
?>
