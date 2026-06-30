<?php

namespace App\Core;

class Config
{
    private static bool $loaded = false;

    public static function init(): void
    {
        if (self::$loaded) return;
        self::$loaded = true;

        define('ENABLE_WORD_HIGHLIGHT', true);
        define('ENABLE_NON_INDONESIAN_WORD_LOGGING', true);
    }
}