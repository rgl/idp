<?php
/**
 * Persona UI support classes.
 *
 * @package OpenID.UI
 */
/**
 * Persona UI support class.
 *
 * @package OpenID.UI
 */
class OpenID_UI_PersonaHelper
{
    /** The labels that are displayed to the user */
    private static $labels = array(
        'nickname'      => 'Alcunha',
        'email'         => 'Endereço de E-mail',
        'fullname'      => 'Nome Completo',
        'dob'           => 'Data de Nascimento',
        'gender'        => 'Sexo',
        'postalcode'    => 'Código Postal',
        'country'       => 'País',
        'language'      => 'Idioma Preferido',
        'timezone'      => 'Fuso Horário'
    );

    /**
     * @param string $key simple registration attribute name
     * @return string the label of a simple registration attribute name
     */
    static function label($key)
    {
        # TODO use gettext or something like that.
        if (array_key_exists($key, OpenID_UI_PersonaHelper::$labels))
            return OpenID_UI_PersonaHelper::$labels[$key];
        return $key;
    }
}
?>
