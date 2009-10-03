<?php
/**
 * Utilities to validate DNS domain names.
 *
 * @package default
 */
/***/

require_once('IllegalArgumentException.php');

/**
 * Validates DNS domain names.
 *
 * @todo make a cron script to update the tlds-alpha-by-domain.txt file
 * @link http://data.iana.org/TLD/tlds-alpha-by-domain.txt  List of valid TLD
 * @link http://tools.ietf.org/html/rfc1035  Domain Names - Implementation and Specification
 * @link http://tools.ietf.org/html/rfc2181  Clarifications to the DNS Specification
 * @link http://tools.ietf.org/html/rfc4343  Domain Name System (DNS) Case Insensitivity Clarification
 * @package default
 */
class DomainName
{
    /**
     * List witl all Top Level Domain names.
     *
     * @var array
     */
    private static $tlds;

    /**
     * Validates a DNS domain name as defined in rfc1035.  This also
     * makes sure the TLD is valid.
     *
     * NB: a label can only start with a letter, but there are plenty
     *     of domain names that start with a digit... so we do not
     *     enforce it here.
     *
     * Example: <code>DomainName::validate('example.com');</code>
     *
     * NB: This ONLY does offline validations.
     *
     * @param string $domain Domain to validate.
     * @exception IllegalArgumentException When the domain is invalid
     */
    static function validate($domain)
    {
        # From RFC1035 "2.3.1. Preferred name syntax":
        #
        # <domain>      ::= <subdomain> | " "
        # <subdomain>   ::= <label> | <subdomain> "." <label>
        # <label>       ::= <letter> [ [ <ldh-str> ] <let-dig> ]
        # <ldh-str>     ::= <let-dig-hyp> | <let-dig-hyp> <ldh-str>
        # <let-dig-hyp> ::= <let-dig> | "-"
        # <let-dig>     ::= <letter> | <digit>
        # <letter>      ::= any one of the 52 alphabetic characters A
        #                   through Z in upper case and a through z in
        #                   lower case
        # <digit>       ::= any one of the ten digits 0 through 9

        # NB: a label can only start with a letter, but there are plenty
        #     of domain names that start with a digit... so we do not
        #     enforce it here.
        #
        # NB: The label can have any data (even binary) in them as
        #     mentioned on RFC2181, though, the domain name we are
        #     interested in are to be used by humans in HTTP/HTML
        #     browsers, so we more-or-less use the preferred name
        #     syntax.

        if (!$domain || $domain[0] == '.' ||
            !preg_match(
                '/^(\\.?[A-Za-z0-9]([A-Za-z0-9\\-]*[A-Za-z0-9])?)+$/',
                $domain)
            )
            throw new IllegalArgumentException('invalid domain');
        $labels = explode('.', $domain);
        $tld = strtolower($labels[count($labels)-1]);
        if (!array_key_exists($tld, self::tlds()))
            throw new IllegalArgumentException('unknown TLD');

        # make sure the domain does not only contain the TLD.
        if (!in_array(OpenID_Config::profile(), array('test', 'development'))) {
            if (count($labels) <= 1)
                throw new IllegalArgumentException('invalid domain');
        } else {
            if (count($labels) < 1)
                throw new IllegalArgumentException('invalid domain');
        }

        # TODO do not trust trust_roots with only the TLD domains,
        #      eg: *.com *.co.uk.  For this, maintain a list of
        #      valid TLD in a file that is daily updated.
        #      icann maintains this information updated, see:
        #      Top-Level Domain (TLD) Verification Tool (I mention
        #      it at notas.odt)
        #      XXX ICANN only maintains the TLD, not the country TLD
        #          like *.co.uk.
    }

    /**
     * Loads the list of all TLD from tlds-alpha-by-domain.txt file and
     * caches it into $tlds.
     *
     * @return array array of string with the TLDs
     */
    static private function tlds()
    {
        if (!self::$tlds) {
            $tlds = array();
            $lines = explode("\n", file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'tlds-alpha-by-domain.txt'));
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line || $line[0] == '#')
                    continue;
                $tld = strtolower($line);
                $tlds[$tld] = true;
            }
            if (in_array(OpenID_Config::profile(), array('test', 'development'))) {
                $tlds['example'] = true;
                $tlds['localhost'] = true;
            }
            self::$tlds = $tlds;
        }
        return self::$tlds;
    }
}
?>
