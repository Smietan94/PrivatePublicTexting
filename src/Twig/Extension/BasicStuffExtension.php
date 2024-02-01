<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\BasicStuffRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BasicStuffExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('Push', [BasicStuffRuntime::class, 'push']),
        ];
    }
}