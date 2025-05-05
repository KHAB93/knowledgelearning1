<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\OrderRepository; 
use Doctrine\ORM\EntityManagerInterface;



final class StripeController extends AbstractController
{
    #[Route('/create-checkout-session/{id}', name: 'app_stripe_checkout')]
    public function checkout(
        int $id,
        Request $request,
        CourseRepository $courseRepository,
        SessionInterface $session
    ): Response {
        $course = $courseRepository->find($id);

        if (!$course) {
            throw $this->createNotFoundException('Cours non trouvé.');
        }

        //  Store course ID in session
        $session->set('purchased_course_id', $course->getId());

       // API Stripe key
        Stripe::setApiKey('sk_test_...'); // Remplace par ta vraie clé

       // Create payment session
        $checkoutSession = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $course->getTitle(),
                    ],
                    'unit_amount' => $course->getPrice() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_user_lesson', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($checkoutSession->url);
    }

    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(Request $request, CourseRepository $courseRepository, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
{
    $courseId = $request->getSession()->get('purchased_course_id');
    
    if (!$courseId) {
        return $this->redirectToRoute('app_user_lesson');
    }

    $course = $courseRepository->find($courseId);

    if (!$course) {
        throw $this->createNotFoundException('Cours non trouvé');
    }

     // Retrieves logged-in user
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos leçons.');
    }

   // Create command
    $order = new Order();
    $order->setUser($user);
    $order->setCreatedAt(new \DateTimeImmutable());
    $order->setIsCompleted(true);
    $order->setStatus('pending'); 
    $order->setTotalPrice($course->getPrice());


     // Retrieve user information
    $order->setFirstName($user->getFirstName());
    $order->setLastName($user->getLastName());
    $order->setPhone($user->getPhone()); 
    $order->setAdresse($user->getAdresse()); 

   // Add city
    if ($user->getCity()) {
        $order->setCity($user->getCity()); 
    }

    // Order product creation
    $orderProduct = new OrderProducts();
    $orderProduct->setOrder($order);  // Link product to order
    $orderProduct->setProduct($course->getProduct()); // Link product to course
    $orderProduct->setQte(1); // Product quantity

    // Add product to order
    $order->addOrderProduct($orderProduct);

  // Start the transaction to ensure that everything is recorded correctly
   $entityManager->beginTransaction();
   try {
       // Customize order and associated products
       $entityManager->persist($order);
       $entityManager->persist($orderProduct);
       $entityManager->flush();  // Save to database
       $entityManager->commit();  // Validate transaction

      // Verification
        dump($order);
        dump($orderProduct);
        exit;  // To test whether the object is persistent

    
        $request->getSession()->remove('cart');

    

   } catch (\Exception $e) {
       // In case of error, cancel the transaction
       $entityManager->rollback();
       throw $e;
   }


  //  Redirect to lesson
    return $this->redirectToRoute('app_lesson_show', ['id' => $course->getId()]);

    // Delete session ID after use (security)
    $request->getSession()->remove('purchased_course_id');
}


    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }
}




