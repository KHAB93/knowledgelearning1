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
    // Retrieve basket data (products and total)
    $data = $cart->getCart($session); // Must contain 'items' and 'total

   // Create a new Order instance
    $order = new Order();

    // Create the order form
    $form = $this->createForm(OrderType::class, $order);
    $form->handleRequest($request);

    // When the form is submitted and valid
    if ($form->isSubmitted() && $form->isValid()) {
        // Persist order with all products and total
        $order->setTotalPrice($data['total']);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setUser($this->getUser());  // If the user is logged in
        
         // Persist products linked to the order
        foreach ($data['items'] as $item) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($item['product']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setOrder($order);  // Link item to command
            $entityManager->persist($orderItem);
        }

         // Save order in database
        $entityManager->persist($order);
        $entityManager->flush();

       // Start payment with Stripe
        $payment = new StripePayment();
        $payment->startPayment($data, 0); // no delivery charges

         // Redirect to Stripe payment URL
        return $this->redirect($payment->getStripeRedirectUrl());
    }

        // Display order form with total
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

    // Retrieve all user commands
    $orders = $orderRepository->findBy(['user' => $user], ['id' => 'DESC']);

    if (!$orders) {
        $this->addFlash('warning', 'Aucune commande trouvée.');
        return $this->redirectToRoute('app_home_page');
    }

    // Retrieve all order products associated with the user
    $orderProducts = [];
    foreach ($orders as $order) {
        $orderProducts = array_merge($orderProducts, $order->getOrderProducts()->toArray());
    }

    // Extract prices for ordered products
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
    $order->setUser($this->getUser()); // Associate user with command
    $order->setTotalPrice($data['total']);  // Update total price
    $order->setCreatedAt(new \DateTimeImmutable()); // Update creation date
    
    // persist command
    $entityManager->persist($order);
    $entityManager->flush(); // To generate the order ID

    // Add products to order
    foreach ($data['cartItems'] as $item) {
        $product = $productRepository->find($item['product']->getId());

        if (!$product) {
            continue; // Security if a product has been deleted
        }

        $orderProduct = new OrderProducts();
        $orderProduct->setOrder($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQte($item['quantity']); 

        // Customize each product linked to the order
        $entityManager->persist($orderProduct);
    }

    // Save changes
    $entityManager->flush();
}







}


   


