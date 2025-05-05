<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\OrderProductsRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseRepository; 
use Symfony\Component\HttpFoundation\Request; 
use Doctrine\ORM\EntityManagerInterface;
use App\Form\CourseType;
use App\Entity\LessonCompletion;



class CourseController extends AbstractController
{
    #[Route('/courses', name: 'app_courses')]
    public function index(OrderRepository $orderRepository, OrderProductsRepository $orderProductsRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Trouver toutes les commandes de l'utilisateur connecté
        $orders = $orderRepository->findBy(['user' => $user]);

        // Récupérer tous les produits de commandes pour cet utilisateur
        $purchasedCourses = [];

        foreach ($orders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                $purchasedCourses[] = $orderProduct->getCourse(); // Ajouter le cours acheté
            }
        }

        // ✅ Ajoute le dump ici pour vérifier les cours achetés
        dump($purchasedCourses);

        // Passer les cours achetés à la vue
        return $this->render('course/index.html.twig', [
            'purchasedCourses' => $purchasedCourses
        ]);
    }

    #[Route('/course/{id}', name: 'course_show')]
public function show(string $id, CourseRepository $courseRepository): Response
{
    // Trouver le cours par son id
    $course = $courseRepository->find($id);

    if (!$course) {
        throw $this->createNotFoundException('Le cours n\'existe pas.');
    }

    return $this->render('course/show.html.twig', [
        'course' => $course
    ]);
}

#[Route('/admin/course/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            $this->addFlash('success', 'Le cours a été créé avec succès !');
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('course/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/courses', name: 'app_course_index')]
    public function indexAdmin(CourseRepository $courseRepository): Response
    {
    $courses = $courseRepository->findAll();

    return $this->render('course/index.html.twig', [
        'courses' => $courses,
    ]);
}

#[Route('/admin/course/{id}/edit', name: 'app_course_edit')]
public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(CourseType::class, $course);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', 'Le cours a été modifié avec succès !');
        return $this->redirectToRoute('app_course_index');
    }

    return $this->render('course/edit.html.twig', [
        'form' => $form->createView(),
        'course' => $course,
    ]);
}

#[Route('/admin/course/{id}/delete', name: 'app_course_delete', methods: ['POST'])]
public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
        $entityManager->remove($course);
        $entityManager->flush();

        $this->addFlash('success', 'Le cours a été supprimé.');
    }

    return $this->redirectToRoute('app_course_index');
}


#[Route('/cours/{id}/valider', name: 'app_validate_course')]
public function validateCourse(
    Course $course,
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();

    $lessons = $course->getLessons();
    $completedLessons = $em->getRepository(LessonCompletion::class)
        ->findBy(['user' => $user]);

    $completedLessonIds = array_map(fn($l) => $l->getLesson()->getId(), $completedLessons);

    foreach ($lessons as $lesson) {
        if (!in_array($lesson->getId(), $completedLessonIds)) {
            $this->addFlash('warning', 'Vous devez terminer toutes les leçons avant de valider ce cours.');
            return $this->redirectToRoute('app_user_lessons');
        }
    }

    // Vérifier si le cours est déjà validé
    $alreadyCompleted = $em->getRepository(CourseCompletion::class)->findOneBy([
        'user' => $user,
        'course' => $course
    ]);

    if (!$alreadyCompleted) {
        $completion = new CourseCompletion();
        $completion->setUser($user);
        $completion->setCourse($course);
        $em->persist($completion);
        $em->flush();
    }

    $this->addFlash('success', 'Cours validé avec succès !');
    return $this->redirectToRoute('app_user_lessons');
}

// Route pour afficher les certifications d'un cours spécifique
#[Route('/course/{id}/certifications', name: 'app_course_certifications')]
public function showCertifications(int $id, CourseRepository $courseRepository, EntityManagerInterface $em): Response
{
    // Récupère l'utilisateur
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Récupère le cours
    $course = $courseRepository->find($id);
    if (!$course) {
        throw $this->createNotFoundException('Cours non trouvé');
    }

    // Vérifie si l'utilisateur a acheté ce cours
    $hasPurchased = false;
    foreach ($user->getOrders() as $order) {
        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if ($product && $product->getCourse() && $product->getCourse()->getId() === $course->getId()) {
                $hasPurchased = true;
                break 2;
            }
        }
    }

    if (!$hasPurchased) {
        $this->addFlash('warning', 'Vous devez acheter ce cours pour accéder aux certifications.');
        return $this->redirectToRoute('app_products');
    }

    // Vérifie si l'utilisateur a complété toutes les leçons du cours
    $lessons = $course->getLessons();
    $completedLessons = $em->getRepository(LessonCompletion::class)->findBy([
        'user' => $user,
        'course' => $course
    ]);

    $hasCompleted = count($completedLessons) === count($lessons);

    if (!$hasCompleted) {
        $this->addFlash('warning', 'Vous devez terminer toutes les leçons pour accéder aux certifications.');
        return $this->redirectToRoute('app_user_lesson');
    }

    $certifications = $course->getCertifications();

    return $this->render('course/certifications.html.twig', [
        'course' => $course,
        'certifications' => $certifications
    ]);
}


}

