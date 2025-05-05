<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Course;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\CourseRepository;
use App\Service\CoursePurchaseService; 
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\CourseCompletion;
use App\Repository\LessonRepository;
use App\Repository\LessonCompletionRepository;
use Doctrine\ORM\EntityManagerInterface;


class LessonController extends AbstractController
{

    private CoursePurchaseService $coursePurchaseService;

    public function __construct(CoursePurchaseService $coursePurchaseService)
    {
        $this->coursePurchaseService = $coursePurchaseService;
    }

    #[Route('/user/courses', name: 'user_courses')]
    public function showPurchasedCourses(CoursePurchaseService $coursePurchaseService): Response
    {
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $purchasedCourses = $coursePurchaseService->getPurchasedCourses($user);

    return $this->render('user/purchased_courses.html.twig', [
        'userPurchasedCourses' => $purchasedCourses,
    ]);
}


    #[Route('/lessons', name: 'app_lesson')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            // Rediriger vers la connexion si l'utilisateur n'est pas connecté
            return $this->redirectToRoute('app_login');
        }

        // Récupère toutes les commandes de l'utilisateur
        $orders = $orderRepository->findBy(['user' => $user]);

        // Récupérer les cours depuis les produits des commandes
        $courses = [];

        foreach ($orders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                // Récupérer le produit associé à l'OrderProduct
                $product = $orderProduct->getProduct();
                dump($product);

                // Récupérer le cours associé à ce produit
                $course = $product ? $product->getCourse() : null;
                dump($course);

                if ($course && !in_array($course, $courses, true)) {
                    // Si un cours est trouvé et qu'il n'est pas déjà dans la liste, l'ajouter
                    $courses[] = $course;
                }
            }
        }

        return $this->render('lesson/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/lessons/{id}', name: 'app_lesson_show')]
public function show(
    int $id,
    CourseRepository $courseRepository,
    OrderRepository $orderRepository,
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $course = $courseRepository->find($id);

    if (!$course) {
        throw $this->createNotFoundException('Cours non trouvé');
    }

    // ✅ Vérifie si l'utilisateur a bien acheté ce cours
    $hasPurchased = false;
    foreach ($user->getOrders() as $order) {
        foreach ($order->getOrderItems() as $orderItem) {
            if ($orderItem->getProduct()->getCourse() === $course) {
                $hasPurchased = true;
                break 2;
            }
        }
    }

    if (!$hasPurchased) {
        $this->addFlash('warning', 'Vous devez acheter ce cours pour y accéder.');
        return $this->redirectToRoute('app_products');
    }

    // ✅ Vérifie si toutes les leçons sont terminées
    $lessons = $course->getLessons();
    $completedLessons = $em->getRepository(LessonCompletion::class)->findBy([
        'user' => $user,
    ]);

    $completedLessonIds = array_map(fn($l) => $l->getLesson()->getId(), $completedLessons);
    $allCompleted = true;

    foreach ($lessons as $lesson) {
        if (!in_array($lesson->getId(), $completedLessonIds)) {
            $allCompleted = false;
            break;
        }
    }

    // ✅ Si toutes les leçons sont terminées, marquer le cours comme terminé
    if ($allCompleted) {
        $existing = $em->getRepository(CourseCompletion::class)->findOneBy([
            'user' => $user,
            'course' => $course,
        ]);

        if (!$existing) {
            $completion = new CourseCompletion();
            $completion->setUser($user);
            $completion->setCourse($course);
            $em->persist($completion);
            $em->flush();
        }
    }

    return $this->render('lesson/show.html.twig', [
        'course' => $course,
        'allCompleted' => $allCompleted, // 👈 pour Twig
    ]);
}

    #[Route('/user/lessons', name: 'app_user_lesson')]
    public function userCoursesLessons(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
      

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos leçons.');
        }
    
        
        // ✅ Récupérer toutes les commandes, sans filtrer le statut
        $orders = $orderRepository->findBy([
            'user' => $user,
        ]);

        // Vérifie si des commandes ont été trouvées
        dump($orders);  // Cela va afficher les commandes dans ton navigateur pour te permettre de vérifier
       
        
        // Extraire les cours associés aux produits des commandes
        $courses = [];
        $orderIds = [];  // On va stocker les IDs de commande ici

        
        foreach ($orders as $order) {

             // Ajouter l'ID de la commande à la liste des IDs
            $orderIds[] = $order->getId();

            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getProduct();
                $course = $product ? $product->getCourse() : null;
            
    
                if ($course && !in_array($course, $courses, true)) {
                    // Ajouter le cours à la liste s'il n'est pas déjà présent
                    $courses[] = $course;
                }

            }


        }

       
        dump($courses);
        // Passer les cours à la vue
        return $this->render('user/pending_courses.html.twig', [
            'courses' => $courses,
            'orderIds' => $orderIds,  // Passer les IDs des commandes à la vue
        ]);
    }

    // 1. Marquer une leçon comme terminée
    #[Route('/lecon/{id}/terminer', name: 'app_finish_lesson')]
    public function finishLesson(
        Lesson $lesson,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        // Vérifie si la leçon est déjà marquée comme terminée
        $existing = $em->getRepository(LessonCompletion::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson
        ]);

        if (!$existing) {
            // Crée une nouvelle entrée pour la leçon terminée
            $completion = new LessonCompletion();
            $completion->setUser($user);
            $completion->setLesson($lesson);
            $em->persist($completion);
            $em->flush();

            $this->addFlash('success', 'Leçon marquée comme terminée !');
        } else {
            $this->addFlash('info', 'Leçon déjà terminée.');
        }

        // Redirige l'utilisateur vers la page de ses leçons
        return $this->redirectToRoute('app_user_lessons');
    }

    // 2. Vérifier les leçons terminées pour chaque cours
    #[Route('/user/lessons', name: 'app_user_lessons')]

    public function userLessons(
        CourseRepository $courseRepository,
        LessonCompletionRepository $lessonCompletionRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos leçons.');
        }

        // Récupérer les commandes de l'utilisateur
        $orders = $user->getOrders();
        $courses = [];

        foreach ($orders as $order) {
            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getProduct();
                $course = $product ? $product->getCourse() : null;

                if ($course && !in_array($course, $courses, true)) {
                    $courses[] = $course;
                }
            }
        }


        $coursesData = [];

        foreach ($courses as $course) {
            $lessons = $course->getLessons();
            $lessonIds = array_map(fn($lesson) => $lesson->getId(), $lessons->toArray());

            // Trouver les leçons déjà complétées
            $completedLessons = $lessonCompletionRepository->findBy([
                'user' => $user,
            ]);

            // Récupérer les IDs des leçons complétées
            $completedLessonIds = array_map(fn($c) => $c->getLesson()->getId(), $completedLessons);

            // Vérifier si toutes les leçons du cours sont terminées
            $allCompleted = count($lessonIds) > 0 && empty(array_diff($lessonIds, $completedLessonIds));

            $coursesData[] = [
                'course' => $course,
                'completed' => $allCompleted,
            ];
        }

        // Passer les données à la vue
        return $this->render('user/pending_courses.html.twig', [
            'coursesData' => $coursesData,
        ]);
    }

    // 3. Valider un cours
    #[Route('/cours/{id}/valider', name: 'app_validate_course')]
    public function validateCourse(
        Course $course,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        // Vérifier si toutes les leçons du cours sont terminées
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

    



}


        

