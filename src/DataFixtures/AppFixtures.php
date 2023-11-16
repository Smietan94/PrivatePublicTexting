<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\MessageFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        MessageFactory::createMany(250);

        $manager->flush();
    }
}
