<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;

class RequestRuntime implements RuntimeExtensionInterface
{
    public function keyList(string $value, string $key, string $separator = ','): array
    {
        $result = [];

        foreach (explode($separator, $value) as $val) {
            $result[] = [$key => $val];
        }

        return $result;
    }
}
