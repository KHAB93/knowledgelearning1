<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\City;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            'Cuisine',
            'informatique',
            'Jardinage',
            'Musique'
        ];

        foreach ($data as $index => $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);

            $this->addReference('category_' . $index, $category);
        }

        $manager->flush();
    }
}

