<?php

namespace App\Service;

use App\Entity\UserCoursePurchase;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;

class CoursePurchaseService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Save course purchase by user
    public function saveCoursePurchase(User $user, Course $course): void
    {
        $purchase = new UserCoursePurchase();
        $purchase->setUser($user);
        $purchase->setCourse($course);
        $purchase->setPurchaseDate(new \DateTimeImmutable());
        
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();
    }

    // Retrieves courses purchased by a user
    public function getPurchasedCourses(User $user): array
    {
        return $this->entityManager
        ->getRepository(UserCoursePurchase::class)
        ->findBy(['user' => $user]);
    }
}
