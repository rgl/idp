<?php
/**
 * OpenID Identi(ty|fier) classes.
 *
 * @package OpenID
 */
/***/

require_once('IllegalArgumentException.php');
require_once('OpenID/Persona.php');
require_once('OpenID/TrustRoot.php');

/**
 * Represents an OpenID Identifier.
 *
 * Identifiers are stored in the database table "identity".
 *
 * @package OpenID
 */
class OpenID_Identifier
{
    private $id;
    private $identity;
    private $username;
    private $disabled;

    /**
     * Initializes an instance.
     *
     * @param int $id ID
     * @param string $itentity the identity URL
     * @param string $username the user associated with this identity
     * @param boolean $disabled true if this identity is disabled
     */
    private function __construct($id, $identity, $username, $disabled=true)
    {
        $this->id = self::validateId($id);
        $this->identity = self::validate($identity);
        $this->username = self::validateUsername($username);
        $this->disabled = $disabled;
    }

    /**
     * Finds an Identifier searching by the identity URL.
     *
     * @param string $identity the identity URL to find
     * @return OpenID_Identifier the found identifier or null when it does no exists
     */
    static function findByIdentity($identity)
    {
        return self::findByField('identity', $identity);
    }

    /**
     * Finds an Identifier associated with the given user.
     *
     * @param string $username user to find
     * @return OpenID_Identifier the found identifier or null when it does no exists
     */
    static function findByUsername($username)
    {
        return self::findByField('username', $username);
    }

    /**
     * Creates a new (unsaved) Identifier.
     *
     * NB: call save to sabe this into the database.
     *
     * @param string $itentity the identity URL
     * @param string $username the username
     * @return OpenID_Identifier
     */
    static function create($identity, $username)
    {
        return new OpenID_Identifier(0, $identity, $username);
    }

    /**
     * @return int ID of this Identity
     */
    function id()
    {
        return $this->id;
    }

    /**
     * @return string identity URL of this Identity
     */
    function identity()
    {
        return $this->identity;
    }

    /**
     * @return string username of this Identity
     */
    function username()
    {
        return $this->username;
    }

    /**
     * Retrieves or set the disabled state of this Identity.
     *
     * @param boolean $disable when not null sets the disabled state.
     * @return boolean the disabled state
     */
    function disabled($disable=null)
    {
        if ($disable !== null)
            $this->disabled = $disable;
        return $this->disabled;
    }

    /**
     * Retrieves a trust root of this Identity.
     *
     * @param string $trust_root Trust root URL
     * @return OpenID_TrustRoot the trust root of this Identifier
     */
    function trustRoot($trust_root)
    {
        return OpenID_TrustRoot::findByTrustRoot($this, $trust_root);
    }

    /**
     * Retrieves all the trust root of this Identity.
     *
     * @param string $trust_root Trust root URL
     * @return OpenID_TrustRoot the trust root of this Identifier
     */
    function trustRoots()
    {
        return OpenID_TrustRoot::findAll($this);
    }

    /**
     * Retrieves all the personas of this Identity.
     *
     * @return array array of OpenID_Persona
     */
    function personas()
    {
        return OpenID_Persona::fromIdentity($this);
    }


    /**
     * @param string $trust_root Trust root URL
     * @return true when the user allways trusts the given trust root.
     */
    function trusts($trustRoot)
    {
        $tr = $this->trustRoot($trustRoot);
        return $tr ? $tr->autoApprove() : false;
    }

    /**
     * Validates the fields of the given $identifier
     *
     * @param OpenID_Identifier $identifier identifier to validate.
     * @return OpenID_Identifier
     * @exception IllegalArgumentException when invalid
     */
    static function validate($identifier)
    {
        if (!$identifier)
            throw new IllegalArgumentException('identifier cannot be empty');
        $url = @parse_url($identifier);
        if (false === $url)
            throw new IllegalArgumentException('identifier invalid');
        $scheme = $url['scheme'];
        if ($scheme != 'http' and $scheme != 'https')
            throw new IllegalArgumentException('identifier with invalid scheme');
        # TODO make further validations?
        return $identifier;
    }

    /**
     * Validates the given $id.
     *
     * @param int $id the id to validate
     * @return int the id
     * @exception IllegalArgumentException when invalid
     */
    static function validateId($id)
    {
        if (!is_int($id) || $id < 0)
            throw new IllegalArgumentException('id is invalid');
        return $id;
    }

    /**
     * Validates the given $username.
     *
     * @param string $username username to validate
     * @return string the username
     * @exception IllegalArgumentException when invalid
     */
    static function validateUsername($username)
    {
        # an username can only contain chars above the SPACE.
        if (!preg_match('/^[\x21-\xff]+$/', $username))
            throw new IllegalArgumentException('username is invalid');
        return $username;
    }


    /**
     * Saves this into the database.
     *
     * @exception Exception when fails
     */
    function save()
    {
        return $this->id == 0 ? $this->insert() : $this->update();
    }

    /**
     * Create and new database row for this identifier.
     *
     * @exception Exception when fails
     */
    private function insert()
    {
        $log = OpenID_Config::logger();
        if ($this->id != 0)
            return;
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'insert into '.self::table('identity').'(identity, username, disabled) '.
                'values(:identity, :username, :disabled)'
            );
            $values = array(
                ':identity' => $this->identity,
                ':username' => $this->username,
                ':disabled' => $this->disabled
            );
            if (!$stmt->execute($values)) {
                throw new Exception('Failed to insert indentity');
            }

            $this->id = intval($db->lastInsertId());

            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to insert identity', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Updates the database row of this identifier.
     *
     * @exception Exception when fails
     */
    private function update()
    {
        $log = OpenID_Config::logger();
        if ($this->id == 0)
            return;
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'update '.self::table('identity').' '.
                'set disabled=:disabled '.
                'where id = :id'
            );
            $values = array(
                ':disabled' => $this->disabled,
                ':id' => $this->id
            );
            if (!$stmt->execute($values))
                throw new Exception('Failed to update indentity');
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to update identity', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    private function db()
    {
        return OpenID_Config::db();
    }

    private function table($name)
    {
        return OpenID_Config::get('db.table.prefix').$name;
    }

    private static function findByField($field, $value)
    {
        $log = OpenID_Config::logger();
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'select id, identity, username, disabled from '.self::table('identity').' ' .
                "where $field=:value"
            );
            $n = 0;
            if ($stmt->execute(array(':value' => $value))) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $id = intval($row['id']);
                    $identity = $row['identity'];
                    $username = $row['username'];
                    $disabled = $row['disabled'] != 0;
                    ++$n;
                }
            }
            # release connection.
            $stmt = null;
            $db = null;

            if ($n == 0)
                return null;
            if ($n != 1)
                throw Exception('Unexpected number of rows returned');
            $o = new OpenID_Identifier($id, $identity, $username, $disabled);
            return $o;
        } catch (Exception $e) {
            $log->debug('Failed to find identity', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }
}
?>
