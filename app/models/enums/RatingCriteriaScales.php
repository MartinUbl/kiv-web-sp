<?php

/**
 * Class of all rating criteria scales
 */
class RatingCriteriaScales
{
    /**
     * Retrieves originality rating scale
     * @return array
     */
    public static function getOriginalityScale()
    {
        return array(
            1 => '1 - Neoriginální',
            2 => '2 - Méně originální',
            3 => '3 - Průměrně originální',
            4 => '4 - Originální',
            5 => '5 - Velmi originální'
        );
    }

    /**
     * Retrieves topic rating scale
     * @return array
     */
    public static function getTopicScale()
    {
        return array(
            1 => '1 - Nezajímavé',
            2 => '2 - Téměř nezajímavé',
            3 => '3 - Neutrální',
            4 => '4 - Zajímavé',
            5 => '5 - Velmi zajímavé'
        );
    }

    /**
     * Retrieves structure rating scale
     * @return array
     */
    public static function getStructureScale()
    {
        return array(
            1 => '1 - Chaos',
            2 => '2 - Mírný chaos',
            3 => '3 - Nekonzistentní',
            4 => '4 - Únosné',
            5 => '5 - Dobře strukturované'
        );
    }

    /**
     * Retrieves language rating scale
     * @return array
     */
    public static function getLanguageScale()
    {
        return array(
            1 => '1 - Nečitelné',
            2 => '2 - Téměř nečitelné',
            3 => '3 - Čitelné s trochou úsilí',
            4 => '4 - Vcelku dobře čitelné',
            5 => '5 - Dobře čitelné'
        );
    }

    /**
     * Retrieves recommendation rating scale
     * @return array
     */
    public static function getRecommendationScale()
    {
        return array(
            1 => '1 - Nedoporučuji',
            2 => '2 - Spíše nedoporučuji',
            3 => '3 - Nevím, jestli doporučit',
            4 => '4 - Spíše doporučuji',
            5 => '5 - Doporučuji'
        );
    }

    /**
     * Retrieves all rating scales
     * @return array
     */
    public static function getAllScales()
    {
        return array(
            'originality' => self::getOriginalityScale(),
            'topic' => self::getTopicScale(),
            'structure' => self::getStructureScale(),
            'language' => self::getLanguageScale(),
            'recommendation' => self::getRecommendationScale()
        );
    }
}
