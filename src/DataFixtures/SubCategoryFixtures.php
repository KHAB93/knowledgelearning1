<?php

namespace App\DataFixtures;

use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SubCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sub = new SubCategory();
        $sub->setName('Cursus d’initiation à la guitare');
        $sub->setCategory($this->getReference('category_3')); // Musique
        $manager->persist($sub);

        $this->addReference('sub_category_0', $sub);

        $manager->flush();
    }
}
