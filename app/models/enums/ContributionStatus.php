<?php

class ContributionStatus
{
    const NEW_CONTRIB = 'new';
    const SUBMITTED = 'submitted';
    const REJECTED = 'rejected';
    const ACCEPTED = 'accepted';

    public static function getStatusTranslations()
    {
        return array(
            self::NEW_CONTRIB => 'nový',
            self::SUBMITTED => 'odeslán',
            self::REJECTED => 'odmítnutý',
            self::ACCEPTED => 'přijatý'
        );
    }
}
