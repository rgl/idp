<?php
/**
 * OpenID Association classes.
 *
 * @package OpenID
 */
/***/

require_once('HMAC.php');
require_once('IllegalArgumentException.php');
require_once('OpenID/KeyValue.php');
require_once('OpenID/AssociationSecret.php');

/**
 * Represents an OpenID Association.
 *
 * @package OpenID
 */
class OpenID_Association
{
    /**
     * Default Diffie-Hellman Modulus parameter for OpenID Associations.
     *
     * @var string
     */
    const Default_DH_Modulus = '155172898181473697471232257763715539915724801966915404479707795314057629378541917580651227423698188993727816152646631438561595825688188889951272158842675419950341258706556549803580104870537681476726513255747040765857479291291572334510643245094715007229621094194349783925984760375594985848253359305585439638443';
    /**
     * Default Diffie-Hellman Gen parameter for OpenID Associations.
     *
     * @var string
     */
    const Default_DH_Gen = '2';

    private $id;
    private $type;
    private $mac;
    private $stateless;
    private $expiresAt;

    /**
     * Extract the hash name from the given association $type.
     *
     * For example: HMAC-SHA1 => SHA1
     *
     * @param string $type association type
     * @return string hash name
     * @exception IllegalArgumentException
     */
    private static function hashName($type)
    {
        if (!preg_match('/^HMAC-([A-Z0-9]+)$/', $type, $matches))
            throw new IllegalArgumentException('Unknown HMAC type.');
        return $matches[1];
    }

    /**
     * The default minimum association lifetime.
     *
     * @return int lifetime
     */
    static function defaultLifetime()
    {
        return OpenID_AssociationSecret::defaultLifetime();
    }

    /**
     * Creates a new Association of the given $type.
     *
     * @param string $type association type
     * @param boolean $stateless whether this is a stateless association or not
     * @return OpenID_Association the association
     * @exception IllegalArgumentException
     */
    static function create($type, $stateless=false)
    {
        # assemble the association handle (id) as:
        #
        #   base64(salt);type;stateless;secret.id
        #
        # the mac_key is assembled as:
        #
        #   H(secret;id)
        #
        # Limits: id MUST be 255 characters or less, and consist only of
        #         ASCII characters in the range 33-126 inclusive (ie
        #         printable non-whitespace characters).
        #         Base64 encoding respects this.
        $secret = OpenID_AssociationSecret::create();
        $expiresAt = $secret->expiresAt();
        $salt = Random::bytes(8);
        $id = implode(
            ';',
            array(
                Base64::encode($salt),
                $type,
                $stateless ? '1' : '0',
                $secret->id()
            )
        );
        $hashName = self::hashName($type);
        $macKey = Hash::run($hashName, implode(';', array($secret->secret(), $id)));
        $mac = HMAC::create($hashName, $macKey);
        return new OpenID_Association($id, $type, $mac, $stateless, $expiresAt);
    }

    /**
     * Opens an existing Association.
     *
     * @param string $id the association ID to open
     * @param boolean $expectsStateless whether we expect to open a stateless association or not
     * @return OpenID_Association the existing association, or null when the association has expired or does not exists
     * @exception Exception
     * @exception IllegalArgumentException
     */
    static function open($id, $expectsStateless=false)
    {
        $log = OpenID_Config::logger();
        try {
            $parts = explode(';', $id);
            # id is assembled as:
            #
            #   base64(salt);type;stateless;secret.id
            if (count($parts) != 4) {
                $log->error('invalid association id='.$id);
                return null;
            }
            list($salt, $type, $stateless, $secretId) = $parts;
            $hashName = self::hashName($type);
            $secretId = intval($secretId);
            $stateless = intval($stateless) != 0;

            # The mac key of a stateless session MUST NOT be shared/used
            # with/by a statefull session.
            if ($expectsStateless != $stateless)
                throw Exception('Michmatch between stateless and expectation');

            $secret = OpenID_AssociationSecret::open($secretId);
            if (!$secret) {
                $log->error('unknown or expired association id='.$id);
                return null;
            }
            $expiresAt = $secret->expiresAt();

            $macKey = Hash::run($hashName, implode(';', array($secret->secret(), $id)));
            $mac = HMAC::create($hashName, $macKey);
            return new OpenID_Association($id, $type, $mac, $stateless, $expiresAt);
        } catch (Exception $e) {
            $log->error('Failed to open association', $e);
            throw $e;
        }
    }

    /**
     * Initializes an OpenID_Association instance.
     *
     * @param string $id the association ID to open
     * @param string $type the association type. eg: HMAC-SHA1
     * @param HMAC $mac the MAC to sign and validate the association and its parameters
     * @param boolean $stateless whether this is a stateless association or not
     * @param int $expiresAt the UNIX timestamp when this association should expire
     */
    function __construct($id, $type, $mac, $stateless, $expiresAt)
    {
        $this->id = $id;
        $this->type = $type;
        $this->mac = $mac;
        $this->stateless = $stateless;
        $this->expiresAt = $expiresAt;
    }

    /**
     * The opaque ID that identifies this association.
     *
     * @return string Association ID
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Type of this association.
     *
     * @return string Association type. eg: HMAC-SHA1
     */
    function type()
    {
        return $this->type;
    }

    /**
     * @return boolean true when this Association is stateless.
     */
    function stateless()
    {
        return $this->stateless;
    }

    /**
     * @return string MAC key we use to sign and validate the association data.
     */
    function macKey()
    {
        return $this->mac->key();
    }

    /**
     * @return boolean true when this association is expired.
     */
    function expired()
    {
        return time() > $this->expiresAt;
    }

    /**
     * Signs the given data using this association mac key.
     *
     * @param string $data data to sign
     * @return string data signature
     */
    private function mac($data)
    {
        return $this->mac->sign($data);
    }

    /**
     * Signs the fields data in the given $fields obtaining the field
     * values from the given $data hash.
     *
     * Example:
     * <code>
     * $data = array(
     *   'identity' => 'http://localhost/id',
     *   'email' => 'rgl@example.com',
     *   'somethingElse' => 'value'
     * );
     * # sign the identity and email fields from $data.
     * $signature = sign('identity,email', $data);
     * </code>
     *
     * @param string $fields comma separated list of field names to sign
     * @param hash $data hash that contains the fields data
     * @return string The binary signature.
     * @exception IllegalArgumentException
     */
    function sign($fields, $data)
    {
        $encode = array();
        if (is_string($fields))
            $fields = explode(',', $fields);
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data))
                throw new IllegalArgumentException('not all fields are inside data');
            $encode[$field] = $data[$field];
        }
        $encoded = OpenID_KeyValue::encode($encode, $fields);
        return $this->mac($encoded);
    }

    /**
     * Verifies Signs the fields data in the given $fields obtaining the field
     * values from the given $data hash.
     *
     * Example:
     * <code>
     * $data = array( # as obtained from the other partie.
     *   'identity' => 'http://localhost/id',
     *   'email' => 'rgl@example.com',
     *   'somethingElse' => 'value'
     * );
     * $signature = ...; # as obtained from the other partie.
     * # verify the signature of identity and email fields from $data.
     * $goodSignature = verify('identity,email', $data, $signature);
     * </code>
     *
     * @param string $fields comma separated list of field names to verify
     * @param hash $data hash that contains the fields data
     * @return boolean true when the $signature is valid
     * @exception IllegalArgumentException
     */
    function verify($fields, $data, $signature)
    {
        $sig = $this->sign($fields, $data);
        return $sig == $signature;
    }
}
?>
