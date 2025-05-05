<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $product = new Product();
        $product->setCourse($this->getReference('course_0'));
        $product->setName('Leçon n°1 : Découverte de l’instrument');
        $product->setDescription('Lorem ipsum dolor sit amet...');
        $product->setPrice(26);
        $product->setImage('guitare.jpg');
        $manager->persist($product);

        $this->addReference('product_0', $product);

        $manager->flush();
    }
}
