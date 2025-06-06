<?php

namespace App\DataFixtures;

use App\Entity\AdventureType;
use App\Enum\AdventureTypeList;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdventureTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (AdventureTypeList::cases() as $case) {
            $type = new AdventureType();
            $type->setName($case->value); // attention, .value (ex: "hiking")
            $manager->persist($type);
        }

        $manager->flush();
    }
}
