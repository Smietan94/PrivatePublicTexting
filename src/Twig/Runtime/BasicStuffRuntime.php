<?php

namespace App\Twig\Runtime;

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
}
