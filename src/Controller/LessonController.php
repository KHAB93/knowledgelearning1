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
            // Redirect to login if user is not logged in
            return $this->redirectToRoute('app_login');
        }

        // Retrieves all user commands
        $orders = $orderRepository->findBy(['user' => $user]);

       // Retrieve prices from order products
        $courses = [];

        foreach ($orders as $order) {
            foreach ($order->getOrderProducts() as $orderProduct) {
                // Retrieve the product associated with the OrderProduct
                $product = $orderProduct->getProduct();
                dump($product);

                // Retrieve the course associated with this product
                $course = $product ? $product->getCourse() : null;
                dump($course);

                if ($course && !in_array($course, $courses, true)) {
                    // If a course is found and is not already in the list, add it.
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

    // Checks if the user has purchased this course
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

     // Checks if all lessons have been completed
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

   //  If all lessons have been completed, mark the course as finished
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
        'allCompleted' => $allCompleted, 
    ]);
}

    #[Route('/user/lessons', name: 'app_user_lesson')]
    public function userCoursesLessons(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
      

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos leçons.');
        }
    
        
        //  Retrieve all orders, without filtering status
        $orders = $orderRepository->findBy([
            'user' => $user,
        ]);

       // Checks whether commands have been found
        dump($orders);  // This will display the commands in your browser so you can check
       
        
        // Extract prices associated with order products
        $courses = [];
        $orderIds = [];  // We'll store the order IDs here

        
        foreach ($orders as $order) {

             // Add order ID to ID list
            $orderIds[] = $order->getId();

            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getProduct();
                $course = $product ? $product->getCourse() : null;
            
    
                if ($course && !in_array($course, $courses, true)) {
                    // Add course to list if not already present
                    $courses[] = $course;
                }
            }
        }

        dump($courses);
        // Switch courses to view
        return $this->render('user/pending_courses.html.twig', [
            'courses' => $courses,
            'orderIds' => $orderIds,  // Pass order IDs to the view
        ]);
    }

    // 1. mark a lesson as completed
    #[Route('/lecon/{id}/terminer', name: 'app_finish_lesson')]
    public function finishLesson(
        Lesson $lesson,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        // Checks whether the lesson has already been marked as completed
        $existing = $em->getRepository(LessonCompletion::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson
        ]);

        if (!$existing) {
             // Creates a new entry for the completed lesson
            $completion = new LessonCompletion();
            $completion->setUser($user);
            $completion->setLesson($lesson);
            $em->persist($completion);
            $em->flush();

            $this->addFlash('success', 'Leçon marquée comme terminée !');
        } else {
            $this->addFlash('info', 'Leçon déjà terminée.');
        }

         // Redirects the user to the lessons page
        return $this->redirectToRoute('app_user_lessons');
    }

    // 2. Check completed lessons for each course
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

        // Retrieve user commands
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

           // Find lessons already completed
            $completedLessons = $lessonCompletionRepository->findBy([
                'user' => $user,
            ]);

            // Retrieve IDs for completed lessons
            $completedLessonIds = array_map(fn($c) => $c->getLesson()->getId(), $completedLessons);

           // Check that all lessons have been completed
            $allCompleted = count($lessonIds) > 0 && empty(array_diff($lessonIds, $completedLessonIds));

            $coursesData[] = [
                'course' => $course,
                'completed' => $allCompleted,
            ];
        }

       // Pass data to view
        return $this->render('user/pending_courses.html.twig', [
            'coursesData' => $coursesData,
        ]);
    }

     // 3. validate a course
    #[Route('/cours/{id}/valider', name: 'app_validate_course')]
    public function validateCourse(
        Course $course,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        // Check that all lessons have been completed
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

        // Check if the course has already been validated
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


        

