<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('bahaa.khatibi@hotmail.fr');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('$2y$13$...');
        $user->setIsActive(false);
        $user->setActivationToken('32a4fb...a1d6');
        $manager->persist($user);

        $this->addReference('user_0', $user);

        $manager->flush();
    }
}
