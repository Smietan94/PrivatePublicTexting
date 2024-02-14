<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\Constants\RouteName;
use Twig\Extension\RuntimeExtensionInterface;

class BasicStuffRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function push(array $array, $argument): array
    {
        if (is_array($argument)) {
            foreach($argument as $arg) {
                array_push($array, $arg);
            }
        } else {
            array_push($array, $argument);
        }

        return $array;
    }

    /**
     * getRouteName
     *
     * @param  string $RouteName // case insensitive
     * @return string
     */
    public function getRouteName(string $RouteName): string
    {
        $reflection = new \ReflectionClass(RouteName::class);

        return $reflection->getConstant(strtoupper($RouteName));
    }
}
