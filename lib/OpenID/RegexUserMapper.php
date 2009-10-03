<?php
/**
 * Flexible User Mapper.
 *
 * @package OpenID
 */
/***/
/**
 * Maps between users and identity URLs using a regular expression array.
 *
 * The configuration syntax is:
 *
 * <code>
 *   OpenID_Config::set('RegexUserMapper.userToIdentity', array(
 *       array('/pattern/flags',  'replacement'),
 *   ));
 *
 *   OpenID_Config::set('RegexUserMapper.identityToUser', array(
 *       array('/pattern/flags',  'replacement'),
 *   ));
 * </code>
 *
 * <pre>
 * pattern
 *    a normal regular expression.
 * replacement
 *    a replacement that transforms the user into an identity URL
 *    segment that is appended into 'idp.identity.url'.
 * </pre>
 *
 * The matching process iterates the array using preg_replace over
 * $user or $identity, stops when the first match is found, and
 * returns the $user or $identity replaced with 'replacement', which
 * should be the resulting identity that is assigned to the user.
 *
 * @package OpenID
 */
class OpenID_RegexUserMapper
{
    /**
     * Maps a user into an identity URL.
     *
     * @param string $user user to map
     * @return string the corresponding identity URL
     */
    function userToIdentity($user)
    {
        $result = self::applyMap('RegexUserMapper.userToIdentity', $user);
        if (!$result)
            return null;
        $url = OpenID_Config::get('idp.identity.url');
        return OpenID_Config::mergeUrl($url, $result);
    }

    /**
     * Maps a identity URL into a user.
     *
     * @param string $identity identity URL to map
     * @return string the corresponding user
     */
    function identityToUser($identity)
    {
        $url = OpenID_Config::get('idp.identity.url').'/';
        if (strpos($identity, $url) !== 0)
            return null;
        $segment = substr($identity, strlen($url));
        return self::applyMap('RegexUserMapper.identityToUser', $segment);
    }

    private static function applyMap($mapName, $subject)
    {
        $map = OpenID_Config::get($mapName);
        if (!$map)
            return null;
        foreach ($map as $entry) {
            list($pattern, $replacement) = $entry;
            $count = 0;
            $segment = preg_replace($pattern, $replacement, $subject, 1, $count);
            if ($count > 0)
                return $segment;
        }
        return null;
    }
}
?>
