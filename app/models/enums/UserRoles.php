<?php

class UserRoles
{
    const AUTHOR = 'author';
    const REVIEWER = 'reviewer';
    const ADMINISTRATOR = 'admin';

    public static function getRoleTranslations()
    {
        return array(
            self::AUTHOR => 'autor',
            self::REVIEWER => 'recenzent',
            self::ADMINISTRATOR => 'administrátor'
        );
    }
}
