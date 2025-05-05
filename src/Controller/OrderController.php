<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Form\OrderType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;
use App\Entity\City;
use App\Service\Cart;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\OrderProducts; 
use App\Repository\OrderRepository;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use App\Service\StripePayment;
use App\Entity\OrderItem;




final class OrderController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private ProductRepository $productRepository
    ) 
    
    {
        $this->productRepository = $productRepository; 
    }

    /**
     * @throws TransportExceptionInterface
     */

    


     #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Cart $cart
    ): Response {
    // Récupérer les données du panier (produits et total)
    $data = $cart->getCart($session); // Doit contenir 'items' et 'total'

    // Créer une nouvelle instance d'Order
    $order = new Order();

    // Créer le formulaire pour la commande
    $form = $this->createForm(OrderType::class, $order);
    $form->handleRequest($request);

    // Lorsque le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        // Persister l'ordre avec tous les produits et le total
        $order->setTotalPrice($data['total']);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setUser($this->getUser());  // Si l'utilisateur est connecté
        
        // Persister les produits liés à la commande
        foreach ($data['items'] as $item) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($item['product']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setOrder($order);  // Lier l'item à la commande
            $entityManager->persist($orderItem);
        }

        // Enregistrer la commande dans la base de données
        $entityManager->persist($order);
        $entityManager->flush();

        // Lancer le paiement avec Stripe
        $payment = new StripePayment();
        $payment->startPayment($data, 0); // pas de frais de livraison

        // Rediriger vers l'URL de paiement Stripe
        return $this->redirect($payment->getStripeRedirectUrl());
    }

    // Afficher le formulaire de commande avec le total
    return $this->render('order/index.html.twig', [
        'form' => $form->createView(),
        'total' => $data['total']
    ]);
}

     
     
     

    #[Route('/admin/order', name: 'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepository):Response
    {
        $order = $orderRepository->findAll();
        //dd($order);

        return $this->render('order/order.html.twig',[
            "orders"=>$order
        ]);
    }

    #[Route('/admin/order/{id}/is-completed/update', name: 'app_orders_is_completed_update')]
    public function isCompletedUpdate($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash(type:'success', message: 'modification effectuée');
        return $this->redirectToRoute('app_orders_show');
    }

    #[Route('/admin/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager):Response
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash(type: 'danger', message: 'Votre commande a été supprimée');
        return $this->redirectToRoute(route: 'app_orders_show');

    }


    #[Route("/order-ok-message", name:'order_ok_message')]
    public function orderMessage ():Response

    {
        return $this->render('order/order_message.twig');
    }
   

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city):Response
    {
        $cityShippingPrice = $city->getShippingCost();

        return new Response(json_encode(['status'=>200, "message"=>'on', 'content'=>$cityShippingPrice]));
    }

    #[Route('/order/lessons', name: 'app_order_lessons')]
public function showBoughtLessons(SessionInterface $session, OrderRepository $orderRepository, OrderProductsRepository $orderProductsRepository): Response
{
    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('warning', 'Veuillez vous connecter pour voir vos leçons.');
        return $this->redirectToRoute('app_home_page');
    }

    // Récupérer toutes les commandes de l'utilisateur
    $orders = $orderRepository->findBy(['user' => $user], ['id' => 'DESC']);

    if (!$orders) {
        $this->addFlash('warning', 'Aucune commande trouvée.');
        return $this->redirectToRoute('app_home_page');
    }

    // Récupérer tous les produits de commande associés à l'utilisateur
    $orderProducts = [];
    foreach ($orders as $order) {
        $orderProducts = array_merge($orderProducts, $order->getOrderProducts()->toArray());
    }

    // Extraire les cours des produits commandés
    $courses = [];
    foreach ($orderProducts as $orderProduct) {
        $product = $orderProduct->getProduct();
        $course = $product ? $product->getCourse() : null;

        if ($course && !in_array($course, $courses, true)) {
            $courses[] = $course;
        }
    }

    return $this->render('order/lessons.html.twig', [
        'courses' => $courses,
    ]);
}

private function persistOrder(
    Order $order,
    array $data,
    EntityManagerInterface $entityManager,
    ProductRepository $productRepository
): void {
    $order->setUser($this->getUser()); // Associer l'utilisateur à la commande
    $order->setTotalPrice($data['total']); // Mettre à jour le prix total
    $order->setCreatedAt(new \DateTimeImmutable()); // Mettre à jour la date de création
    
    // Persister la commande
    $entityManager->persist($order);
    $entityManager->flush(); // Pour générer l'ID de la commande

    // Ajouter les produits de la commande
    foreach ($data['cartItems'] as $item) {// Attention ici, 'items' doit être la bonne clé
        $product = $productRepository->find($item['product']->getId());

        if (!$product) {
            continue; // Sécurité si un produit a été supprimé
        }

        $orderProduct = new OrderProducts();
        $orderProduct->setOrder($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQte($item['quantity']); // Quantité du produit

        // Persister chaque produit lié à la commande
        $entityManager->persist($orderProduct);
    }

    // Enregistrer les changements
    $entityManager->flush();
}







}


   


