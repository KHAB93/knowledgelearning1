<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\CoursePurchaseService;
use App\Entity\User;
use App\Entity\Course;
use App\Entity\UserCoursePurchase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CoursePurchaseServiceTest extends TestCase
{
    public function testSaveCoursePurchase()
    {
        // 1. Créer un mock pour l'EntityManager
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // On s'assure que persist et flush seront appelés une fois
        $entityManagerMock->expects($this->once())
                          ->method('persist')
                          ->with($this->isInstanceOf(UserCoursePurchase::class));
        $entityManagerMock->expects($this->once())
                          ->method('flush');

        // 2. Créer le service avec le mock
        $service = new CoursePurchaseService($entityManagerMock);

        // 3. Créer des objets User et Course factices
        $user = new User();
        $course = new Course();

        // 4. Appeler la méthode à tester
        $service->saveCoursePurchase($user, $course);

        // Si persist et flush sont appelés correctement, le test passe
        $this->assertTrue(true);
    }

    public function testGetPurchasedCourses()
{
    $user = new User();

    $repositoryMock = $this->createMock(EntityRepository::class);
    $repositoryMock->expects($this->once())
                   ->method('findBy')
                   ->with($this->callback(function ($criteria) use ($user) {
                       return isset($criteria['user']) && $criteria['user'] === $user;
                   }))
                   ->willReturn(['purchase1', 'purchase2']);

    $entityManagerMock = $this->createMock(EntityManagerInterface::class);
    $entityManagerMock->method('getRepository')
                      ->with(UserCoursePurchase::class)
                      ->willReturn($repositoryMock);

    $service = new CoursePurchaseService($entityManagerMock);
    $purchases = $service->getPurchasedCourses($user);

    $this->assertCount(2, $purchases);
}

}
