<?php

namespace App\DataFixtures;

use App\Entity\OrderItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrderItemFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $item = new OrderItem();
        $item->setProduct($this->getReference('product_0'));
        $item->setOrder($this->getReference('order_0'));
        $item->setQuantity(1);
        $manager->persist($item);

        $manager->flush();
    }
}
