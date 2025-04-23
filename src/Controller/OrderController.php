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
    public function index(Request $request,SessionInterface $session, 
     ProductRepository $productRepository,
     EntityManagerInterface $entityManager,
     Cart $cart, 
    
     ): Response
    {
        
            $data = $cart->getCart($session);

            $order = new Order();
            $form = $this->createForm(OrderType::class, $order);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){

                if($order->isPayOnDelivery()){
                    if (!empty($data['total'])){
                        $order->setTotalPrice($data['total']);
                        $order->setCreatedAt(new \DateTimeImmutable());
                        $entityManager->persist($order);
                        $entityManager->flush();
                        
                        foreach ($data['cart'] as $value){
                        $orderProduct = new OrderProducts();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($value['product']);
                        $orderProduct->setQte($value['quantity']);
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                        }
                    }

                    $session->set('cart', []);

                    $html = $this->renderView('mail/orderConfirm.html.twig',[
                        'order'=>$order
                    ]);

                    $email = (new Email())
                    ->from('bahaa.khatibi@hotmail.fr')
                    ->to('to@mail.com')
                    ->subject('Confirmation de réception de la commande')
                    ->html($html);

                    $this->mailer->send($email);

                    return $this->redirectToRoute('order_ok_message');

                }

                $payment = new StripePayment();

                $shippingCost = $order->getCity()->getShippingCost();

                $payment->startPayment($data, $shippingCost);

                $stripeRedirectUrl = $payment->getStripeRedirectUrl();

                //dd($stripeRedirectUrl);
                
                
                return $this->redirect($stripeRedirectUrl);
            }

            return $this->render('order/index.html.twig', [
                'form'=>$form->createView(),
                'total'=>$data['total']
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
   


}