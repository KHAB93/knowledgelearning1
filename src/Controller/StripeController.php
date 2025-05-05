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

        // ✅ Stocker l'ID du cours dans la session
        $session->set('purchased_course_id', $course->getId());

        // ✅ Clé API Stripe
        Stripe::setApiKey('sk_test_...'); // Remplace par ta vraie clé

        // ✅ Création de la session de paiement
        $checkoutSession = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $course->getTitle(),
                    ],
                    'unit_amount' => $course->getPrice() * 100, // En centimes
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

    // Récupère l'utilisateur connecté
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos leçons.');
    }

    // Création de la commande
    $order = new Order();
    $order->setUser($user);
    $order->setCreatedAt(new \DateTimeImmutable());
    $order->setIsCompleted(true);
    $order->setStatus('pending'); 
    $order->setTotalPrice($course->getPrice());

    


    // Récupération des informations de l'utilisateur
    $order->setFirstName($user->getFirstName());
    $order->setLastName($user->getLastName());
    $order->setPhone($user->getPhone()); 
    $order->setAdresse($user->getAdresse()); 

    // Ajouter la ville
    if ($user->getCity()) {
        $order->setCity($user->getCity()); 
    }

    // Création du produit de la commande
    $orderProduct = new OrderProducts();
    $orderProduct->setOrder($order);  // Lier le produit à la commande
    $orderProduct->setProduct($course->getProduct());  // Lier le produit au cours
    $orderProduct->setQte(1);  // Quantité du produit

    // Ajouter le produit à la commande
    $order->addOrderProduct($orderProduct);

   // Commencer la transaction pour garantir que tout soit enregistré correctement
   $entityManager->beginTransaction();
   try {
       // Persister la commande et les produits associés
       $entityManager->persist($order);
       $entityManager->persist($orderProduct);
       $entityManager->flush();  // Enregistrer dans la base de données
       $entityManager->commit();  // Valider la transaction

       // Vérification
        dump($order);
        dump($orderProduct);
        exit;  // Pour tester si l'objet est bien persistant

        // ✅ Vider le panier ici
        $request->getSession()->remove('cart');

    

   } catch (\Exception $e) {
       // En cas d'erreur, annuler la transaction
       $entityManager->rollback();
       throw $e;
   }


    // ✅ Redirection vers la leçon
    return $this->redirectToRoute('app_lesson_show', ['id' => $course->getId()]);

    // Supprime l'ID de session après usage (sécurité)
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




