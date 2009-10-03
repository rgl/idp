<?php
/**
 * OpenID Trust Root classes.
 *
 * @package OpenID
 */
/***/

require_once('DomainName.php');
require_once('OpenID/Persona.php');

/**
 * Represents an OpenID Trust Root.
 *
 * Trust Roots are stored in the database table "trust_root".
 *
 * @package OpenID
 */
class OpenID_TrustRoot
{
    private $id;
    private $identityId;
    private $personaId;
    private $trustRoot;
    private $autoApprove;
    private $approveCount;
    private $approveCountIncrement;

    /**
     * Initializes an instance.
     *
     * @param int $id ID
     * @param int $identityId the ID of the identity we are associated with
     * @param int $personaId the ID of the persona we are associated with
     * @param string $trustRoot the trust root URL
     * @param boolean $autoApprove true if this trust root should be automatically authenticated without user intervention
     * @param int $approveCount number of times the user has authenticated to this trust root
     */
    private function __construct($id, $identityId, $personaId, $trustRoot, $autoApprove, $approveCount)
    {
        # TODO validate data.
        $this->id = $id;
        $this->identityId = $identityId;
        $this->personaId = $personaId;
        $this->trustRoot = $trustRoot;
        $this->autoApprove = $autoApprove;
        $this->approveCount = $approveCount;
        $this->approveCountIncrement = 0;
    }

    /**
     * Create a new Trust Root.
     *
     * @param OpenID_Identifier $identity the identity we are associated with
     * @param OpenID_TrustRoot $trustRoot the trust root we are associated with 
     * @param boolean $autoApprove true if this trust root should be automatically authenticated without user intervention
     */
    static function create($identity, $trustRoot, $autoApprove=false)
    {
        return new OpenID_TrustRoot(0, $identity->id(), null, $trustRoot, $autoApprove, 0);
    }

    /**
     * Finds a TrustRoot searching by the identity URL.
     *
     * @param OpenID_Identifier $identity the identity of the TrustRoot we are about to find
     * @param string $trustRoot the trust root URL we are about to find
     * @return OpenID_TrustRoot the found trust root or null when it does no exists
     */
    static function findByTrustRoot($identity, $trustRoot)
    {
        return self::findByField($identity, 'trust_root', $trustRoot);
    }

    /**
     * Finds all TrustRoot associated with the given identity.
     *
     * @param OpenID_Identifier $identity the identity of the TrustRoot we are about to find
     * @return array array of OpenID_TrustRoot with the found trust roots
     */
    static function findAll($identity)
    {
        return self::findAllByField($identity, '1', 1);
    }

    /**
     * Validate the given $trustRoot URL.
     *
     * @param string $trustRoot to validate
     * @exception IllegalArgumentException
     * @return string $trustRoot
     */
    static function validate($trustRoot)
    {
        if (!$trustRoot)
            throw new IllegalArgumentException('trustRoot cannot be empty');
        $url = @parse_url($trustRoot);
        if (false === $url)
            throw new IllegalArgumentException('trustRoot invalid');
        $scheme = $url['scheme'];
        if ($scheme != 'http' and $scheme != 'https')
            throw new IllegalArgumentException('trustRoot with invalid scheme');

        $host = $url['host'];
        $isWildcard = substr($host, 0, 2) == '*.';
        if ($isWildcard)
            $host = substr($host, 2);
        DomainName::validate($host);

        # TODO make further validations?
        # TODO normalize trailing slash /

        return $trustRoot;
    }


    /** @return int ID */
    function id()
    {
        return $this->id;
    }

    /** @return string trust root URL */
    function trustRoot()
    {
        return $this->trustRoot;
    }

    /** @return boolean true when this trust root should be automatically authenticated */
    function autoApprove($newValue=null)
    {
        if ($newValue === null)
            return $this->autoApprove;
        $this->autoApprove = $newValue === true;
    }

    /** @return int number of times we have authenticated to this trust root */
    function approveCount($newValue=null)
    {
        if ($newValue === null)
            return $this->approveCount;
        $i = $newValue - $this->approveCount;
        $this->approveCountIncrement += $i;
        $this->approveCount += $i;
    }

    /** @param OpenID_Persona $newValue associates with the given persona */
    function persona($newValue=null)
    {
        if ($newValue === null) {
            return OpenID_Persona::findById($this->identityId, $this->personaId);
        }
        # TODO handle unsaved persona... eg. throw exception if unsaved.
        # TODO make sure the new persona has the same identity as this trust root.
        $this->personaId = $newValue->id();
    }


    function save()
    {
        return $this->id == 0 ? $this->insert() : $this->update();
    }

    function insert()
    {
        $log = OpenID_Config::logger();

        if ($this->id != 0)
            throw new Exception('cannot insert an existing trust root');
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'insert into '.self::table('trust_root').
                '(identity_id, persona_id, trust_root, auto_approve, approve_count) '.
                'values(:identity_id, :persona_id, :trust_root, :auto_approve, :approve_count)'
            );
            $values = array(
                ':identity_id' => $this->identityId,
                ':persona_id' => $this->personaId,
                ':trust_root' => $this->trustRoot,
                ':auto_approve' => $this->autoApprove,
                ':approve_count' => $this->approveCount,
            );
            if (!$stmt->execute($values)) {
                throw new Exception('failed to insert trust root');
            }
            $this->id = intval($db->lastInsertId());
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to insert trust root', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    function update()
    {
        $log = OpenID_Config::logger();

        if ($this->id == 0)
            throw new Exception('cannot update a new trust root');
        $db = self::db();
        try {
            $sql =
                'update '.self::table('trust_root').' set '.
                'persona_id=:persona_id, ' .
                'auto_approve=:auto_approve, ' .
                'approve_count=approve_count+:approve_count_increment ' .
                'where id=:id';
            $stmt = $db->prepare($sql);
            $values = array(
                ':id' => $this->id,
                ':persona_id' => $this->personaId,
                ':auto_approve' => $this->autoApprove,
                ':approve_count_increment' => $this->approveCountIncrement,
            );
            if (!$stmt->execute($values)) {
                throw new Exception('failed to update trust root');
            }
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to update trust root', $e);
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

    private static function findByField($identity, $field, $value)
    {
        $result = self::findAllByField($identity, $field, $value);
        switch (count($result)) {
            case 0:
                return null;
            case 1:
                return $result[0];
            default:
                throw Exception('Unexpected number of rows returned');
        }
    }

    private static function findAllByField($identity, $field, $value)
    {
        $log = OpenID_Config::logger();
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'select id, persona_id, trust_root, auto_approve, approve_count from '.self::table('trust_root').' ' .
                "where identity_id=:identity_id and $field=:value"
            );
            $values = array(
                ':identity_id' => $identity->id(),
                ':value' => $value
            );
            $rows = array();
            if ($stmt->execute($values)) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $id = intval($row['id']);
                    $persona_id = $row['persona_id'];
                    $trust_root = $row['trust_root'];
                    $auto_approve = $row['auto_approve'] == '1';
                    $approve_count = intval($row['approve_count']);
                    $rows[]= new OpenID_TrustRoot($id, $identity->id(), $persona_id, $trust_root, $auto_approve, $approve_count);
                }
            }
            # release connection.
            $stmt = null;
            $db = null;
            return $rows;
        } catch (Exception $e) {
            $log->debug('Failed to find trust root', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }
}
?>
