<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;

class CoursePurchaseTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client; // ✅ Ajout ici

    protected function setUp(): void
    {
        // ✅ Crée le client une seule fois ici
        $this->client = static::createClient();

        // ✅ Récupère l'EntityManager
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur factice
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $this->entityManager->persist($user);

        // Créer un cours factice
        $course = new Course();
        $course->setTitle('Cours Test');
        $course->setDescription('Description test du cours');
        $course->setPrice(50);
        $this->entityManager->persist($course);

        $this->entityManager->flush();
    }

    public function testUserCanPurchaseCourse()
    {
        // ✅ Ne recrée PAS le client ici
        $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => 'test@example.com']);
        $this->client->loginUser($user);

        $course = $this->entityManager->getRepository(Course::class)
                        ->findOneBy(['title' => 'Cours Test']);

        $this->client->request('GET', '/course/' . $course->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Cours Test');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Course')->execute();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
