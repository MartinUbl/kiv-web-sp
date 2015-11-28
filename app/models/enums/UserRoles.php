<?php

/**
 * Enumerator of all user roles
 */
class UserRoles
{
    const AUTHOR = 'author';
    const REVIEWER = 'reviewer';
    const ADMINISTRATOR = 'admin';

    /**
     * Retrieves translation array
     * @return array
     */
    public static function getRoleTranslations()
    {
        return array(
            self::AUTHOR => 'autor',
            self::REVIEWER => 'recenzent',
            self::ADMINISTRATOR => 'administrátor'
        );
    }
}
