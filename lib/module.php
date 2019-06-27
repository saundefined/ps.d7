<?php

namespace Ps\D7;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

class Module
{
    const BITRIX_PREFIX = 'bitrix';

    public static function autoLoad($className) {
        $class = array_values(array_filter(explode('\\', mb_strtolower($className))));

        if ($class[0] === self::BITRIX_PREFIX) {
            try {
                Loader::includeModule($class[1]);
            } catch (LoaderException $e) {
            }
        } else {
            Loader::includeSharewareModule($class[0] . '.' . $class[1]);
        }
    }
}
