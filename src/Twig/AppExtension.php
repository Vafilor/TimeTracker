<?php

declare(strict_types=1);

namespace App\Twig;

use DateInterval;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct()
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('keyList', [RequestRuntime::class, 'keyList']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('zeroDuration', [$this, 'zeroDuration']),
        ];
    }

    public function zeroDuration(): DateInterval
    {
        return new DateInterval('PT0S');
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }
}
