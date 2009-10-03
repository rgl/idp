<?php
/**
 * OpenID Association auxiliary classes.
 *
 * @package OpenID
 */
/***/

require_once('Base64.php');

/**
 * This class manages a poll of secrets to be used in the Association
 * fase of OpenID.
 *
 * The secret poll is stored in the database table "association_secret".
 *
 * Configuration properties:
 *
 * - idp.association.secret.count
 *   number of secrets that are stored inside the poll.   
 * - idp.association.lifetime
 *   the association lifetime or the number of seconds the secrets will remain on the poll.
 *
 * @package OpenID
 * @see OpenID_Config
 */
class OpenID_AssociationSecret
{
    private $id;
    private $expiresAt;
    private $secret;

    /**
     * Creates a secret that can be used for at least
     * 'idp.association.lifetime' seconds.
     *
     * @return OpenID_AssociationSecret
     * @exception Exception
     */
    static function create()
    {
        $log = OpenID_Config::logger();

        # we devide time in $lifetime blocks.  each block can have at
        # most $secretCount secrets created.
        # NB: because of race conditions, sometimes we can have more
        #     than $secretCount secrets.  but thats OK...

        # the number of secrets we can create for a given time block.
        $secretCount = self::defaultSecretCount();
        $lifetime = self::defaultLifetime(); # [seconds]

        # round $expiresAt to the muliple of $lifetime that is above or equal $expiresAt.
        $expiresAt = time() + $lifetime;
        $expiresAt = floor($expiresAt / ($lifetime + 1)) * $lifetime + $lifetime;

        # if there are at least $secretCount secrets on the database,
        # randomly select one of them;  otherwise, create a new
        # secret.
        # NB: there is an inherent race between the time we decide
        #     and the time we actually create a new secret, but thats
        #     OK.

        $db = self::db();
        try {
            # if the poll is full, use one of the secrets at random.
            $stmt = $db->prepare(
                'select id, expires_at, secret from '.self::table('association_secret').' ' .
                'where expires_at >= :expires_at '.
                'limit '.intval($secretCount)
            );
            $stmt->bindParam(':expires_at', $expiresAt);
            if ($stmt->execute()) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) >= $secretCount) {
                    $row = $rows[rand(0, count($rows) - 1)];
                    $id = intval($row['id']);
                    $expiresAt = intval($row['expires_at']);
                    $secret = Base64::decode($row['secret']);
                    $stmt = null;
                    $db = null;
                    return new OpenID_AssociationSecret($id, $expiresAt, $secret);
                }
            }

            # the poll is not full;  create a new secret.
            $secret = Random::bytes(20);
            $stmt = $db->prepare(
                'insert into '.self::table('association_secret').'(expires_at, secret) ' .
                'values(:expires_at, :secret)'
            );
            $values = array(
                ':expires_at' => $expiresAt,
                ':secret' => Base64::encode($secret)
            );
            if (!$stmt->execute($values))
                throw new Exception('failed to insert association secret');
            $id = intval($db->lastInsertId());
            $log->debug('Created new association secret id='.$id);
            # release the connection.
            $stmt = null;
            $db = null;
            return new OpenID_AssociationSecret($id, $expiresAt, $secret);
        } catch (Exception $e) {
            $log->error('Failed to insert association into database', $e);
            # release the connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Opens an existing (and non expired) secret.
     *
     * @return OpenID_AssociationSecret the secret, or null when the secret has expired or does not exists.
     * @exception Exception
     */
    static function open($id)
    {
        $log = OpenID_Config::logger();
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'select expires_at, secret from '.self::table('association_secret').' ' .
                'where id=:id'
            );
            $n = 0;
            if ($stmt->execute(array(':id' => $id))) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $expiresAt = intval($row['expires_at']);
                    $secret = Base64::decode($row['secret']);
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

            $associationSecret = new OpenID_AssociationSecret($id, $expiresAt, $secret);

            # If this secret has expired, destroy it.
            if ($associationSecret->expired()) {
                $log->debug('Destroying expired association secret id='.$associationSecret->id);
                $associationSecret->destroy();
                return null;
            }

            return $associationSecret;
        } catch (Exception $e) {
            $log->error('Failed to open association secret', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Destroy this association secret.
     *
     * NB: This is also a static function.
     *
     * @param int $id secret id, or null to destroy this secret.
     * @exception Exception
     */
    function destroy($id=null)
    {
        $log = OpenID_Config::logger();
        if (isset($this))
            $id = $this->id;
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'delete from '.self::table('association_secret').' ' .
                'where id=:id'
            );
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            # clean up.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->error('Failed to destroy association secret', $e);
            # clean up.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Destroys expired association secrets.
     *
     * @exception Exception
     */
    static public function destroyExpired()
    {
        $db = self::db();
        try {
            $now = time();
            $stmt = $db->prepare(
                'delete from '.self::table('association_secret').' ' .
                'where expires_at < :now'
            );
            $stmt->bindParam(':now', $now);
            $stmt->execute();
            # clean up.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->error('Failed to destroy expired association secrets', $e);
            # clean up.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Initializes this instance.
     *
     * @param int $id secret ID
     * @param int $expiresAt UNIX time when this secret should expire
     * @param string $secret the secret
     */
    private function __construct($id, $expiresAt, $secret)
    {
        $this->id = $id;
        $this->expiresAt = $expiresAt;
        $this->secret = $secret;
    }

    /**
     * @return int number of secrets that are stored inside the poll
     */
    static function defaultSecretCount()
    {
        return OpenID_Config::get('idp.association.secret.count');
    }

    /**
     * @return int the association lifetime or the number of seconds the secrets will remain on the poll
     */
    static function defaultLifetime()
    {
        return OpenID_Config::get('idp.association.lifetime');
    }

    static private function db()
    {
        return OpenID_Config::db();
    }

    static private function table($name)
    {
        return OpenID_Config::get('db.table.prefix').$name;
    }

    /**
     * @return int this secret ID
     */
    function id()
    {
        return $this->id;
    }

    /**
     * @return int UNIX time when this secrete should expire
     */
    function expiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return boolean true if this secret has expired, false otherwise
     */
    function expired()
    {
        return time() > $this->expiresAt;
    }

    /**
     * @return string the secret
     */
    function secret()
    {
        return $this->secret;
    }
}
?>
