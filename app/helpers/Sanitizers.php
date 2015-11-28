<?php

/**
 * Class containing all sanitizers and validators for inputs
 */
abstract class Sanitizers
{
    /** everything's fine */
    const OK = 'ok';
    /** input is too short */
    const TOO_SHORT = 'tooshort';
    /** input is too long */
    const TOO_LONG = 'toolong';
    /** input contains bad characters */
    const BAD_CHARS = 'badchars';
    /** input does not match other input */
    const DO_NOT_MATCH = 'nomatch';
    /** input is already being used elsewhere (typically username not being unique) */
    const ALREADY_IN_USE = 'alreadyinuse';
    /** input not found */
    const IS_MISSING = 'missing';
    /** input is required, but hasn't been supplied */
    const IS_REQUIRED = 'required';
    /** unknown error */
    const UNKNOWN = 'unknown';
    /** input does not match database value, or so */
    const NOT_VALID_DB = 'invaliddb';

    /** minimal username length */
    const MIN_USERNAME_LENGTH = 5;
    /** maximal username length */
    const MAX_USERNAME_LENGTH = 32;
    /** maximal real name length */
    const MAX_REALNAME_LENGTH = 64;

    /** minimal password length */
    const MIN_PASS_LENGTH = 6;
    /** maximal password length */
    const MAX_PASS_LENGTH = 32;

    /** subject is "male" */
    const SUBJECT_HE = 0;
    /** subject is "female" */
    const SUBJECT_SHE = 1;
    /** subject is "neither male or female" */
    const SUBJECT_IT = 2;

    /**
     * Validate presence of fields in array
     * @param array $source source array
     * @param array $fields fields to be present
     * @param array $missing reference to array with missing fields
     * @return boolean
     */
    public static function validateFieldsPresence($source, $fields, &$missing)
    {
        $missing = array();

        // look for all fields in source
        foreach ($fields as $fld)
        {
            if (!isset($source[$fld]))
                $missing[] = $fld;
        }

        // no missing - okay
        if (count($missing) === 0)
            return true;
        return false;
    }

    /**
     * Sanitize username for general stuff
     * @param string $username
     * @return string
     */
    public static function sanitizeUsername($username)
    {
        if (strlen($username) === 0)
            return self::IS_REQUIRED;

        if (strlen($username) < self::MIN_USERNAME_LENGTH)
            return self::TOO_SHORT;

        if (strlen($username) > self::MAX_USERNAME_LENGTH)
            return self::TOO_LONG;

        if (!preg_match('/^[a-zA-Z0-9\.\-]+$/', $username))
            return self::BAD_CHARS;

        return self::OK;
    }

    /**
     * Sanitize real name for general stuff
     * @param string $name
     * @return string
     */
    public static function sanitizeRealName($name)
    {
        if (strlen($name) === 0)
            return self::IS_REQUIRED;

        if (strlen($name) < 2)
            return self::TOO_SHORT;

        if (strlen($name) > self::MAX_REALNAME_LENGTH)
            return self::TOO_LONG;

        if (!preg_match('/^[\wa-zA-Zěščřžýáíéúůťóň]+$/', $name))
            return self::BAD_CHARS;

        return self::OK;
    }

    /**
     * Sanitize email for general stuff
     * @param string $email
     * @return string
     */
    public static function sanitizeEmail($email)
    {
        if (strlen($email) === 0)
            return self::IS_REQUIRED;

        if (!preg_match('/^[a-zA-Z0-9\.]+@[a-zA-Z0-9\.]+\.[a-z]{2,}$/', $email))
            return self::BAD_CHARS;

        return self::OK;
    }

    /**
     * Sanitize general string for length and required status
     * @param string $str
     * @param boolean $required
     * @param int $minlength
     * @param int $maxlength
     * @return string
     */
    public static function sanitizeGeneralString($str, $required, $minlength, $maxlength)
    {
        if (strlen($str) === 0 && $required)
            return self::IS_REQUIRED;

        if (strlen($str) < $minlength)
            return self::TOO_SHORT;

        if (strlen($str) > $maxlength)
            return self::TOO_LONG;

        return self::OK;
    }

    /**
     * Validates password
     * @param string $pass
     * @return string
     */
    public static function validatePassword($pass)
    {
        if (strlen($pass) === 0)
            return self::IS_REQUIRED;

        if (strlen($pass) < self::MIN_PASS_LENGTH)
            return self::TOO_SHORT;

        if (strlen($pass) > self::MAX_PASS_LENGTH)
            return self::TOO_LONG;

        return self::OK;
    }

    /**
     * When we can't decide, which error to show, use this
     * @var array
     */
    private static $unknownErrorStr = array(
        'je nesprávně zadaný', 'je nesprávně zadaná', 'je nesprávně zadané'
    );

    /**
     * Map of all errors - when value is array, it supports "genders"
     * @var array
     */
    private static $errorMsgMap = array(
        Sanitizers::TOO_LONG => array('je příliš dlouhý', 'je příliš dlouhá', 'je příliš dlouhé'),
        Sanitizers::TOO_SHORT => array('je příliš krátký', 'je příliš krátká', 'je příliš krátké'),
        Sanitizers::DO_NOT_MATCH => 'nesouhlasí',
        Sanitizers::ALREADY_IN_USE => array('už je používán', 'už je používána', 'už je používáno'),
        Sanitizers::BAD_CHARS => 'obsahuje neplatné znaky',
        Sanitizers::IS_MISSING => array('nebyl zadán', 'nebyla zadána', 'nebylo zadáno'),
        Sanitizers::IS_REQUIRED => 'je povinná položka',
        Sanitizers::UNKNOWN => 'neexistuje',
        Sanitizers::NOT_VALID_DB => array('není platný', 'není platná', 'není platné')
    );

    /**
     * Creates error message based on inputs
     * @param string $subject
     * @param string $error
     * @param string $gender
     * @return string
     */
    public static function createErrorMessage($subject, $error, $gender)
    {
        // invalid gender range
        if ($gender < 0 || $gender > 2)
            return $subject.' - chyba';

        // if we know something about that error..
        if (isset(self::$errorMsgMap[$error]))
        {
            // if it's an array, use gender-based message
            if (is_array(self::$errorMsgMap[$error]))
                return $subject.' '.self::$errorMsgMap[$error][$gender];
            else // otherwise use one-value
                return $subject.' '.self::$errorMsgMap[$error];
        }

        // fallback to general error string
        return $subject.' '.self::$unknownErrorStr[$gender];
    }
}
