<?php
# modify PHP include path to include our libraries directories.
ini_set(
    'include_path',
    implode(
        PATH_SEPARATOR,
        array(
            realpath(dirname(__FILE__).'/../lib'),
            ini_get('include_path')
        )
    )
);

/**
 * Alias to htmlspecialchars (in ENT_COMPAT mode).
 *
 * This will convert the following characters to HTML entities:
 *
 *  * '&' (ampersand) becomes '&amp;'
 *  * '"' (double quote) becomes '&quot;'
 *  * '<' (less than) becomes '&lt;'
 *  * '>' (greater than) becomes '&gt;'
 */
function h($html)
{
	return htmlspecialchars($html);
}

require_once(dirname(__FILE__).'/../config/config.php');
?>
