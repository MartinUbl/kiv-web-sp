<?php

/**
 * Miscellanous helpers
 */
abstract class MiscHelpers
{
    /**
     * Method used for hashing password
     * @param string $pass
     * @return string
     */
    public static function passwordHash($pass)
    {
        return hash("sha256", $pass);
    }
}
