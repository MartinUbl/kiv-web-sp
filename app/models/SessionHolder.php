<?php

class SessionHolder
{
    private static function isStarted()
    {
        if (php_sapi_name() !== 'cli')
        {
            if (version_compare(phpversion(), '5.4.0', '>='))
                return (session_status() === PHP_SESSION_ACTIVE);
            else
                return (session_id() !== '');
        }

        return false;
    }

    public static function start()
    {
        if (!self::isStarted())
            session_start();
    }

    public static function destroy()
    {
        if (self::isStarted())
        {
            session_unset();
            session_destroy();
        }
    }

    public static function getLoggedUserId()
    {
        return self::getVariable("login", "user-id");
    }

    public static function setLoggedUserId($id)
    {
        self::setVariable("login", "user-id", $id);
    }
    
    private static function getVariableIdentifier($namespace, $valueid)
    {
        return $namespace.'__'.$valueid;
    }

    public static function getVariable($namespace, $valueid)
    {
        $name = self::getVariableIdentifier($namespace, $valueid);
        if (!isset($_SESSION[$name]))
            return null;

        return $_SESSION[$name];
    }

    public static function setVariable($namespace, $valueid, $value)
    {
        $_SESSION[self::getVariableIdentifier($namespace, $valueid)] = $value;
    }
}
