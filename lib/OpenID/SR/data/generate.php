<?php
/**
 * You need to manually download som files into this directory for this
 * to work.  See bellow how to get them.
 *
 * this file auto generates the following files:
 *
 * <pre>
 *  - AutoGeneratedCountry.php  : Country Codes
 *     * needs iso_3166.xml (see http://packages.debian.org/cgi-bin/search_packages.pl?searchon=names&version=all&exact=1&keywords=iso-codes)
 *       svn cat svn://svn.debian.org/pkg-isocodes/trunk/iso-codes/iso_3166/iso_3166.xml > iso_3166.xml
 *  - AutoGeneratedLanguage.php : Language Codes
 *     * needs iso_639.xml (see http://packages.debian.org/cgi-bin/search_packages.pl?searchon=names&version=all&exact=1&keywords=iso-codes)
 *       svn cat svn://svn.debian.org/pkg-isocodes/trunk/iso-codes/iso_639/iso_639.xml > iso_639.xml
 *  - AutoGeneratedTimeZone.php : Time Zones Codes
 *     * needs zones.tab (see http://www.twinsun.com/tz/tz-link.htm)
 *       wget ftp://elsie.nci.nih.gov/pub/tzdata2007f.tar.gz
 * </pre>
 *
 * @package OpenID.SR
 */


function path($filename)
{
    return dirname(__FILE__).'/'.$filename;
}


function getCountries()
{
/*
Here's how to list all the country names, and theirs two leter code:

 * open iso_3166/iso_3166.xml
 * iterate over: iso_3166_entry
        DTD:
            <!ATTLIST iso_3166_entry
                    alpha_2_code            CDATA   #REQUIRED
                    alpha_3_code            CDATA   #REQUIRED
                    numeric_code            CDATA   #REQUIRED
                    common_name             CDATA   #IMPLIED
                    name                    CDATA   #REQUIRED
                    official_name           CDATA   #IMPLIED
            >

        example:
            <iso_3166_entry
                    alpha_2_code="PT"
                    alpha_3_code="PRT"
                    numeric_code="620"
                    name="Portugal"
                    official_name="Portuguese Republic" />
 * the name of the country is @name and the two leter code id @alpha_2_code;  both of these attributes are always present on the XML element.
 * the translation is inside, eg: iso_3166/iso_3166/pt.po
   the key is the @name attribute.
*/

    $data = array();
    $xml = simplexml_load_file(path('iso_3166.xml'));
    foreach ($xml->iso_3166_entry as $entry) {
            $code = (string) $entry['alpha_2_code'];
            $name = (string) $entry['name'];
            $data[$code] = array('name' => $name);
    }
    return $data;
}

function generateCountry()
{
    $exportedData = var_export(getCountries(), true);

    $src = <<<EOS
<?php
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
# !!                                                           !!
# !!   This file is autogenerated by generate.php              !!
# !!                                                           !!
# !!   DO NOT change it.  ALL changes will be lost after you   !!
# !!   regenerate it again.                                    !!
# !!                                                           !!
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
/**
 * Support data classes for the Simple Registration Extension.
 */
/**
 * Raw list of all Countries.
 *
 * @see OpenID_SR_Country
 * @package OpenID.SR.data
 */
class OpenID_SR_data_AutoGeneratedCountry
{
    /**
     * ISO-3166-2 Country Codes.
     *
     * This Hash is keyed by the Two Letter Country Code, and each
     * element is a Hash with a single key 'name'.
     *
     * Example:
     *
     * <code>
     * \$data = array(
     *     'PT' => array('name' => 'Portugal'),
     * );
     * </code>
     *
     * @var hash
     */
    static \$data = $exportedData;
}
?>
EOS;
    file_put_contents(path('AutoGeneratedCountry.php'), $src);
}


function getLanguages()
{
/*
Here's how to list all languagues, and theirs two leter code:

 * open iso_639/iso_639.xml
 * iterate over: iso_639_entry
        DTD:
        <!ATTLIST iso_639_entry
                iso_639_2B_code         CDATA   #REQUIRED
                iso_639_2T_code         CDATA   #REQUIRED
                iso_639_1_code          CDATA   #IMPLIED
                name                    CDATA   #REQUIRED
        >
 * the name of the language is @name and the two leter code id @iso_639_1_code
   the name attribute is required; BUT when the language has no two leter code, the iso_639_1_code is not present.
 * the translation is inside, eg: iso_639/pt.po
   the key is the @iso_639_1_code attribute that preceeds the translation, eg:

        #. name for por, pt
        msgid "Portuguese"
        msgstr "PortuguÍs"

   OR you can use the msdgid with the @name as key,

   NB: If there is no translation, the msgstr is empty;  so just use the untranslated name.

   regex:

     /#\. name for[\s,]+pt\s*\nmsgid ".+"\nmsgstr "(.*)"\n/m
*/
    $data = array();
    $xml = simplexml_load_file(path('iso_639.xml'));
    foreach ($xml->iso_639_entry as $entry) {
            $code = (string) $entry['iso_639_1_code'];
            # skip entries without a two letter code.
            if (!$code)
                    continue;
            $name = (string) $entry['name'];
            $data[$code] = array('name' => $name);
    }
    return $data;
}

function generateLanguage()
{
    $exportedData = var_export(getLanguages(), true);

    # TODO output translated names too.

    $src = <<<EOS
<?php
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
# !!                                                           !!
# !!   This file is autogenerated by generate.php              !!
# !!                                                           !!
# !!   DO NOT change it.  ALL changes will be lost after you   !!
# !!   regenerate it again.                                    !!
# !!                                                           !!
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
/**
 * Support data classes for the Simple Registration Extension.
 */
/**
 * Raw list of all Languages.
 *
 * @see OpenID_SR_Language
 * @package OpenID.SR.data
 */
class OpenID_SR_data_AutoGeneratedLanguage
{
    /**
     * ISO-639-2 Language Codes.
     *
     * This Hash is keyed by the Two Letter Language Code, and each
     * element is a Hash with a single key 'name'.
     *
     * Example:
     *
     * <code>
     * \$data = array(
     *     'pt' => array('name' => 'Portuguese'),
     * );
     * </code>
     *
     * @var hash
     */
    static \$data = $exportedData;
}
?>
EOS;
    file_put_contents(path('AutoGeneratedLanguage.php'), $src);
}

function getTimeZone()
{
    $data = array();
    $zoneToCountry = array();
    $f = fopen(path('zone.tab'), 'r');
    while (!feof($f)) {
        $line = trim(fgets($f));
        if (!$line || $line[0] == '#')
            continue;
        # country-code\tcoordinates\tTZ\tcomments\n
        $fields = explode("\t", $line, 4);
        $countryCode = $fields[0];
        $tz = $fields[2];
        $data[$countryCode][] = array(
            'TZ' => $tz,
            #'pt_PT' => getTranslatedTZ($tz)
        );
        $zoneToCountry[$tz][] = $countryCode;
    }
    fclose($f);
    return array($data, $zoneToCountry);
}

function generateTimeZone()
{
    list($data, $zoneToCountry) = getTimeZone();
    $exportedData = var_export($data, true);
    $exportedZoneToCountry = var_export($zoneToCountry, true);

    # TODO output translated names too.

    $src = <<<EOS
<?php
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
# !!                                                           !!
# !!   This file is autogenerated by generate.php              !!
# !!                                                           !!
# !!   DO NOT change it.  ALL changes will be lost after you   !!
# !!   regenerate it again.                                    !!
# !!                                                           !!
# !! WARNING ! WARNING ! WARNING ! WARNING ! WARNING ! WARNING !!
/**
 * Support data classes for the Simple Registration Extension.
 */
/**
 * Raw list of all Time Zones.
 *
 * @see OpenID_SR_TimeZone
 * @see OpenID_SR_Country
 * @package OpenID.SR.data
 */
class OpenID_SR_data_AutoGeneratedTimeZone
{
    /**
     * Country Code to Country Time Zones mapping.
     *
     * This Hash is keyed by the Two Letter Country Code, and each
     * element is a Array with all the Time Zones that exist in the
     * respective country.  Each Time Zone is a Hash with a single
     * key 'TZ' with the Time Zone code.
     *
     * Example:
     *
     * <code>
     * \$data = array(
     *     'AQ' => array(
     *         array('TZ' => 'Antarctica/McMurdo'),
     *         array('TZ' => 'Antarctica/South_Pole'),
     *     ),
     * );
     * </code>
     *
     * @var hash
     */
    static \$data = $exportedData;

    /**
     * Zone Code to Country Codes mapping.
     *
     * This Hash is keyed by the Time Zone code, and each element is a
     * Array with all the Countries that have the respective Time Zone.
     *
     * Example:
     *
     * <code>
     * \$data = array(
     *     'Antarctica/McMurdo' => array('AQ'),
     * );
     * </code>
     *
     * @var hash
     */
    static \$zoneToCountry = $exportedZoneToCountry;
}
?>
EOS;
    file_put_contents(path('AutoGeneratedTimeZone.php'), $src);
}


generateCountry();
generateLanguage();
generateTimeZone();
?>
