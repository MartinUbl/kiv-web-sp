<?php

abstract class MiscHelpers
{
    public static function passwordHash($pass)
    {
        return hash("sha256", $pass);
    }
}
