<?php
/**
 * OpenID Helper classes.
 *
 * @package OpenID
 */
/**
 * Miscellaneous helper class.
 *
 * @package OpenID
 */
class OpenID_Helper
{
    /**
     * Collect "openid." prefixed arguments from $parameters
     *
     * Also remove that prefix from argument names.
     *
     * @param hash $parameters hash from where to extract the OpenID related parameters
     * @return hash OpenID related parameters
     */ 
	static function parametersFromRequest($parameters)
    {
        $request = array();
        foreach ($parameters as $key => $value) {
            if (0 !== strpos($key, 'openid.'))
                continue;
            $key = substr($key, 7);
            $request[$key] = $value;
        }
        return $request;
    }

    /**
     * Prefixes all the hash keys with "openid.".
     *
     * @param hash $parameters all the parameters
     * @return hash prefixed $parameters
     */ 
    static function prefixParameters($parameters)
    {
        $result = array();
        foreach ($parameters as $key => $value) {
            $key = "openid.$key";
            $result[$key] = $value;
        }
        return $result;
    }
}
?>
