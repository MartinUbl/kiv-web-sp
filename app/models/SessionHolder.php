<?php

/**
 * Wrapper class for all session-related stuff
 */
class SessionHolder
{
    /**
     * Is session active and running?
     * @return boolean
     */
    private static function isStarted()
    {
        // several functions has been added in php 5.4
        // disallow determining session stuff when running from command line
        if (php_sapi_name() !== 'cli')
        {
            // are we on PHP 5.4 or higher?
            if (version_compare(phpversion(), '5.4.0', '>='))
                return (session_status() === PHP_SESSION_ACTIVE);
            else
                return (session_id() !== '');
        }

        return false;
    }

    /**
     * Start session, if needed
     */
    public static function start()
    {
        if (!self::isStarted())
            session_start();
    }

    /**
     * Destroy session, if possible; also unsets all session stuff
     */
    public static function destroy()
    {
        if (self::isStarted())
        {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Retrieves user ID from session storage
     * @return int
     */
    public static function getLoggedUserId()
    {
        return self::getVariable("login", "user-id");
    }

    /**
     * Sets user ID to session storage
     * @param int $id
     */
    public static function setLoggedUserId($id)
    {
        self::setVariable("login", "user-id", $id);
    }

    /**
     * Builds variable identifier for session storage
     * @param string $namespace
     * @param string $valueid
     * @return string
     */
    private static function getVariableIdentifier($namespace, $valueid)
    {
        return $namespace.'__'.$valueid;
    }

    /**
     * Retrieves variable from session storage, or null if not set
     * @param string $namespace
     * @param string $valueid
     * @return string|null
     */
    public static function getVariable($namespace, $valueid)
    {
        $name = self::getVariableIdentifier($namespace, $valueid);
        if (!isset($_SESSION[$name]))
            return null;

        return $_SESSION[$name];
    }

    /**
     * Sets variable to session storage
     * @param string $namespace
     * @param string $valueid
     * @param string $value
     */
    public static function setVariable($namespace, $valueid, $value)
    {
        $_SESSION[self::getVariableIdentifier($namespace, $valueid)] = $value;
    }
}
