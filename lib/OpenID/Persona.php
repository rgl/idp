<?php
/**
 * Persona related classes.
 *
 * @package OpenID
 */
/***/

/**
 * Represents an Persona.
 *
 * A Persona is a construct we use to let the user disclose different
 * sets of data to different Consumers.  This set of data is defined
 * by the Simple Registration OpenID extension.
 *
 * A Persona belongs-to one OpenID_Identity.
 *
 * This is stored in the database table "persona".
 *
 * @package OpenID
 */
class OpenID_Persona
{
    private $id;
    private $identityId;
    private $name;
    private $srNickname;
    private $srEmail;
    private $srFullname;
    private $srDobYear;
    private $srDobMonth;
    private $srDobDay;
    private $srGender;
    private $srPostalcode;
    private $srCountry;
    private $srLanguage;
    private $srTimezone;

    private function __construct($id, $identityId, $name, $srNickname, $srEmail, $srFullname, $srDobYear, $srDobMonth, $srDobDay, $srGender, $srPostalcode, $srCountry, $srLanguage, $srTimezone)
    {
        # TODO validate data.
        $this->id = $id;
        $this->identityId = $identityId;
        $this->name = $name;
        $this->srNickname = $srNickname;
        $this->srEmail = $srEmail;
        $this->srFullname = $srFullname;
        $this->srDobYear = $srDobYear;
        $this->srDobMonth = $srDobMonth;
        $this->srDobDay = $srDobDay;
        $this->srGender = $srGender;
        $this->srPostalcode = $srPostalcode;
        $this->srCountry = $srCountry;
        $this->srLanguage = $srLanguage;
        $this->srTimezone = $srTimezone;
    }

    static function create($identity, $name, $srNickname=null, $srEmail=null, $srFullname=null, $srDobYear=null, $srDobMonth=null, $srDobDay=null, $srGender=null, $srPostalcode=null, $srCountry=null, $srLanguage=null, $srTimezone=null)
    {
        return new OpenID_Persona(0, $identity->id(), $name, $srNickname, $srEmail, $srFullname, $srDobYear, $srDobMonth, $srDobDay, $srGender, $srPostalcode, $srCountry, $srLanguage, $srTimezone);
    }


    function id()
    {
        return $this->id;
    }

    function name()
    {
        return $this->name;
    }

    function setName($new)
    {
        $this->name = $new;
    }

    function srNickname()
    {
        return $this->srNickname;
    }

    function setSrNickname($new)
    {
        $this->srNickname = $new;
    }

    function srEmail()
    {
        return $this->srEmail;
    }

    function setSrEmail($new)
    {
        $this->srEmail = $new;
    }

    function srFullname()
    {
        return $this->srFullname;
    }

    function setSrFullname($new)
    {
        $this->srFullname = $new;
    }

    function srDobYear()
    {
        return $this->srDobYear;
    }

    function setSrDobYear($new)
    {
        $this->srDobYear = $new;
    }

    function srDobMonth()
    {
        return $this->srDobMonth;
    }

    function setSrDobMonth($new)
    {
        $this->srDobMonth = $new;
    }

    function srDobDay()
    {
        return $this->srDobDay;
    }

    function setSrDobDay($new)
    {
        $this->srDobDay = $new;
    }

    /** @return string the DOB as a YYYY-MM-DD string. */
    function srDob()
    {
        return sprintf('%04d-%02d-%02d', $this->srDobYear, $this->srDobMonth, $this->srDobDay);
    }

    function srGender()
    {
        return $this->srGender;
    }

    function setSrGender($new)
    {
        $this->srGender = $new;
    }

    function srPostalcode()
    {
        return $this->srPostalcode;
    }

    function setSrPostalcode($new)
    {
        $this->srPostalcode = $new;
    }

    function srCountry()
    {
        return $this->srCountry;
    }

    function setSrCountry($new)
    {
        $this->srCountry = $new;
    }

    function srLanguage()
    {
        return $this->srLanguage;
    }

    function setSrLanguage($new)
    {
        $this->srLanguage = $new;
    }

    function srTimezone()
    {
        return $this->srTimezone;
    }

    function setSrTimezone($new)
    {
        $this->srTimezone = $new;
    }

    /**
     * Retrieves a value of a simple registration attribute.
     *
     * @param string $key attribute name
     * @return mixed attribute value
     */
    function get($key)
    {
        # validate key name.
        if (!preg_match('/^[a-z]+$/i', $key))
            return null;
        # get the value.
        $method = 'sr'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if (method_exists($this, $method)) {
            $value = $this->$method();
            return $value;
        }
        return null;
    }


    /** @return array array of string with all the Simple Registration attribute names */
    static function attributeNames()
    {
        return array(
            'nickname',
            'email',
            'fullname',
            'dob',
            'gender',
            'postalcode',
            'country',
            'language',
            'timezone'
        );
    }

    /**
     * Finds a Persona by ID.
     *
     * @param int|OpenID_Identity $identity the Identity associated with the Persona we are looking for
     * @param int $id the ID of the Persona we are looking for
     * @return OpenID_Persona the persona, or null when it does not exists.
     * @exception Exception
     */
    static function findById($identity, $id)
    {
        return self::findOneByField($identity, 'id', $id);
    }

    /**
     * Finds a Persona by Name.
     *
     * @param int|OpenID_Identity $identity the Identity associated with the Persona we are looking for
     * @param string $name the name of the Persona we are looking for
     * @return OpenID_Persona the persona, or null when it does not exists.
     * @exception Exception
     */
    static function findByName($identity, $name)
    {
        return self::findOneByField($identity, 'name', $name);
    }

    private static function findOneByField($identity, $field, $value)
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
        $identity_id = is_int($identity) ? $identity : $identity->id();
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'select id, identity_id, name, sr_nickname, sr_email, sr_fullname, sr_dob_year, sr_dob_month, sr_dob_day, sr_gender, sr_postalcode, sr_country, sr_language, sr_timezone from '.self::table('persona').' ' .
                "where $field=:value and identity_id=:identity_id"
            );
            $values = array(
                ':value' => $value,
                ':identity_id' => $identity_id
            );
            $rows = array();
            if ($stmt->execute($values)) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $id = intval($row['id']);
                    $identity_id = intval($row['identity_id']);
                    $name = $row['name'];
                    $sr_nickname = $row['sr_nickname'];
                    $sr_email = $row['sr_email'];
                    $sr_fullname = $row['sr_fullname'];
                    $sr_dob_year = $row['sr_dob_year'];
                    $sr_dob_month = $row['sr_dob_month'];
                    $sr_dob_day = $row['sr_dob_day'];
                    $sr_gender = $row['sr_gender'];
                    $sr_postalcode = $row['sr_postalcode'];
                    $sr_country = $row['sr_country'];
                    $sr_language = $row['sr_language'];
                    $sr_timezone = $row['sr_timezone'];
                    $rows[]= new OpenID_Persona(
                        $id,
                        $identity_id,
                        $name,
                        $sr_nickname,
                        $sr_email,
                        $sr_fullname,
                        $sr_dob_year,
                        $sr_dob_month,
                        $sr_dob_day,
                        $sr_gender,
                        $sr_postalcode,
                        $sr_country,
                        $sr_language,
                        $sr_timezone
                    );
                }
            }
            # release connection.
            $stmt = null;
            $db = null;
            return $rows;
        } catch (Exception $e) {
            $log->debug('Failed to find persona', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    /**
     * Finds all Personas associated with the given $identity.
     *
     * @param OpenID_Identifier $identity the Identity associated with the Personas we are looking for
     * @returns array array of OpenID_Persona with all the Personas associated with the given identity
     */
    static function fromIdentity($identity)
    {
        $log = OpenID_Config::logger();
        $personas = array();
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'select id, identity_id, name, sr_nickname, sr_email, sr_fullname, sr_dob_year, sr_dob_month, sr_dob_day, sr_gender, sr_postalcode, sr_country, sr_language, sr_timezone from '.self::table('persona').' ' .
                'where identity_id=:identity_id '.
                'order by name'
            );
            $values = array(
                ':identity_id' => $identity->id()
            );
            if ($stmt->execute($values)) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $id = intval($row['id']);
                    $identity_id = intval($row['identity_id']);
                    $name = $row['name'];
                    $sr_nickname = $row['sr_nickname'];
                    $sr_email = $row['sr_email'];
                    $sr_fullname = $row['sr_fullname'];
                    $sr_dob_year = $row['sr_dob_year'];
                    $sr_dob_month = $row['sr_dob_month'];
                    $sr_dob_day = $row['sr_dob_day'];
                    $sr_gender = $row['sr_gender'];
                    $sr_postalcode = $row['sr_postalcode'];
                    $sr_country = $row['sr_country'];
                    $sr_language = $row['sr_language'];
                    $sr_timezone = $row['sr_timezone'];
                    $personas[] = new OpenID_Persona(
                        $id,
                        $identity_id,
                        $name,
                        $sr_nickname,
                        $sr_email,
                        $sr_fullname,
                        $sr_dob_year,
                        $sr_dob_month,
                        $sr_dob_day,
                        $sr_gender,
                        $sr_postalcode,
                        $sr_country,
                        $sr_language,
                        $sr_timezone
                    );
                }
            }
            # release connection.
            $stmt = null;
            $db = null;

            if (count($personas) == 0)
                return null;

            return $personas;
        } catch (Exception $e) {
            $log->debug('Failed to load personas', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }

    function validate()
    {
        # TODO validate all fields, and throw ValidationException on
        #      failure.
    }

    function save()
    {
        $this->validate();
        return $this->id == 0 ? $this->insert() : $this->update();
    }

    function insert()
    {
        $log = OpenID_Config::logger();
        if ($this->id != 0)
            throw new Exception('cannot insert an existing persona');
        $db = self::db();
        try {
            $stmt = $db->prepare(
                'insert into '.self::table('persona').
                '(identity_id, name, sr_nickname, sr_email, sr_fullname, sr_dob_year, sr_dob_month, sr_dob_day, sr_gender, sr_postalcode, sr_country, sr_language, sr_timezone) '.
                'values(:identity_id, :name, :sr_nickname, :sr_email, :sr_fullname, :sr_dob_year, :sr_dob_month, :sr_dob_day, :sr_gender, :sr_postalcode, :sr_country, :sr_language, :sr_timezone)'
            );
            $values = array(
                ':identity_id' => $this->identityId,
                ':name' => $this->name,
                ':sr_nickname' => $this->srNickname,
                ':sr_email' => $this->srEmail,
                ':sr_fullname' => $this->srFullname,
                ':sr_dob_year' => $this->srDobYear,
                ':sr_dob_month' => $this->srDobMonth,
                ':sr_dob_day' => $this->srDobDay,
                ':sr_gender' => $this->srGender,
                ':sr_postalcode' => $this->srPostalcode,
                ':sr_country' => $this->srCountry,
                ':sr_language' => $this->srLanguage,
                ':sr_timezone' => $this->srTimezone,
            );
            if (!$stmt->execute($values)) {
                throw new Exception('failed to insert persona');
            }
            $this->id = intval($db->lastInsertId());
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to insert persona', $e);
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
            throw new Exception('cannot update a new persona');
        $db = self::db();
        try {
            $sql =
                'update '.self::table('persona').' set '.
                'identity_id=:identity_id, '.
                'name=:name, '.
                'sr_nickname=:sr_nickname, '.
                'sr_email=:sr_email, '.
                'sr_fullname=:sr_fullname, '.
                'sr_dob_year=:sr_dob_year, '.
                'sr_dob_month=:sr_dob_month, '.
                'sr_dob_day=:sr_dob_day, '.
                'sr_gender=:sr_gender, '.
                'sr_postalcode=:sr_postalcode, '.
                'sr_country=:sr_country, '.
                'sr_language=:sr_language, '.
                'sr_timezone=:sr_timezone '.
                'where id=:id';
            $stmt = $db->prepare($sql);
            $values = array(
                ':id' => $this->id,
                ':identity_id' => $this->identityId,
                ':name' => $this->name,
                ':sr_nickname' => $this->srNickname,
                ':sr_email' => $this->srEmail,
                ':sr_fullname' => $this->srFullname,
                ':sr_dob_year' => $this->srDobYear,
                ':sr_dob_month' => $this->srDobMonth,
                ':sr_dob_day' => $this->srDobDay,
                ':sr_gender' => $this->srGender,
                ':sr_postalcode' => $this->srPostalcode,
                ':sr_country' => $this->srCountry,
                ':sr_language' => $this->srLanguage,
                ':sr_timezone' => $this->srTimezone
            );
            if (!$stmt->execute($values)) {
                throw new Exception('failed to update persona');
            }
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to update persona', $e);
            # release connection.
            $stmt = null;
            $db = null;
            throw $e;
        }
    }


    public function delete()
    {
        $log = OpenID_Config::logger();

        if ($this->id == 0)
            return;
        $db = self::db();
        try {
            $sql =
                'delete from '.self::table('persona').' '.
                'where id=:id';
            $stmt = $db->prepare($sql);
            $values = array(
                ':id' => $this->id
            );
            if (!$stmt->execute($values)) {
                throw new Exception('failed to delete persona');
            }
            # release connection.
            $stmt = null;
            $db = null;
        } catch (Exception $e) {
            $log->debug('Failed to delete persona', $e);
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
}
?>
