<?php
/**
 * Simple User Mapper.
 *
 * @package OpenID
 */
/**
 * Maps between users and identity URLs.
 *
 * @see OpenID_RegexUserMapper
 * @package OpenID
 */
class OpenID_UserMapper
{
    /**
     * Maps a user into an identity URL.
     *
     * @param string $user user to map
     * @return string the corresponding identity URL
     */
    function userToIdentity($user)
    {
        $url = OpenID_Config::get('idp.identity.url');
        return OpenID_Config::mergeUrl($url, $user);
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
        return substr($url, strlen($url));
    }
}
