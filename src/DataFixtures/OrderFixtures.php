<?php

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $order = new Order();
        $order->setCity($this->getReference('city_0'));
        $order->setUser($this->getReference('user_0'));
        $order->setFirstName('joe');
        $order->setLastName('JOHN');
        $order->setPhone('0000000000');
        $order->setAdresse('avenue du parc');
        $order->setCreatedAt(new \DateTimeImmutable('2025-05-05 08:12:11'));
        $order->setTotalPrice(20);
        $order->setStatus('pending');
        $manager->persist($order);

        $this->addReference('order_0', $order);

        $manager->flush();
    }
}

