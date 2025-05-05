<?php

namespace App\DataFixtures;

use App\Entity\City;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;



class CityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cities = [
            ['Lyon', 3],
            ['Paris', 5],
            ['Marseille', 4],
        ];

        foreach ($cities as $index => [$name, $cost]) {
            $city = new City();
            $city->setName($name);
            $city->setShippingCost($cost);
            $manager->persist($city);

            $this->addReference('city_' . $index, $city);
        }

        $manager->flush();
    }
}
