<?php

declare(strict_types=1);

namespace App\Util;

class TypeUtil
{
    public static function getClassName($obj)
    {
        $classname = get_class($obj);

        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }

        return $pos;
    }
}
