<?php

abstract class Sanitizers
{
    const OK = 'ok';
    const TOO_SHORT = 'tooshort';
    const TOO_LONG = 'toolong';
    const BAD_CHARS = 'badchars';
    const DO_NOT_MATCH = 'nomatch';
    const ALREADY_IN_USE = 'alreadyinuse';
    const IS_MISSING = 'missing';
    const IS_REQUIRED = 'required';
    const UNKNOWN = 'unknown';
    const NOT_VALID_DB = 'invaliddb';

    const MIN_USERNAME_LENGTH = 5;
    const MAX_USERNAME_LENGTH = 32;
    const MAX_REALNAME_LENGTH = 64;

    const MIN_PASS_LENGTH = 6;
    const MAX_PASS_LENGTH = 32;

    const SUBJECT_HE = 0;
    const SUBJECT_SHE = 1;
    const SUBJECT_IT = 2;

    public static function validateFieldsPresence($source, $fields, &$missing)
    {
        $missing = array();

        foreach ($fields as $fld)
        {
            if (!isset($source[$fld]))
                $missing[] = $fld;
        }

        if (count($missing) === 0)
            return true;
        return false;
    }

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

    public static function sanitizeEmail($email)
    {
        if (strlen($email) === 0)
            return self::IS_REQUIRED;

        if (!preg_match('/^[a-zA-Z0-9\.]+@[a-zA-Z0-9\.]+\.[a-z]{2,}$/', $email))
            return self::BAD_CHARS;

        return self::OK;
    }

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

    private static $unknownErrorStr = array(
        'je nesprávně zadaný', 'je nesprávně zadaná', 'je nesprávně zadané'
    );

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

    public static function createErrorMessage($subject, $error, $gender)
    {
        if ($gender < 0 || $gender > 2)
            return $subject.' - chyba';

        if (isset(self::$errorMsgMap[$error]))
        {
            if (is_array(self::$errorMsgMap[$error]))
                return $subject.' '.self::$errorMsgMap[$error][$gender];
            else
                return $subject.' '.self::$errorMsgMap[$error];
        }

        return $subject.' '.self::$unknownErrorStr[$gender];
    }
}
