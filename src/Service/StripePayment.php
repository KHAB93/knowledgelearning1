<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;


class StripePayment
{
    private $redirectUrl;

    public function __construct(){
        Stripe::setApiKey($_SERVER['STRIPE_SECRET']); 
        Stripe::setApiVersion('2024-06-20');
    }

    public function startPayment($cart,$shippingCost){
        
        $cartProducts = $cart['items'];

        $products = [
            [
            'qte'=> 1,
            'price' => $shippingCost,
            'name' => "frais de livraison"
            ]
        ];
        
        foreach ($cartProducts as $value){
            $productItem = []; 
            $productItem['name']= $value['product']->getName();
            $productItem['price']= $value['product']->getPrice();
            $productItem['qte']= $value['quantity'];
            $products[]= $productItem; 
        }
        
        $session = Session::create([
            'line_items'=>[
                array_map(fn(array $product)=>[
                    'quantity'=> $product['qte'],
                    'price_data' => [
                        'currency'=> 'EUR',
                        'product_data'=> [
                            'name' => $product['name']
                        ],
                        'unit_amount' => $product['price']*100
                    ],
                
                ],$products)
            ],
            'mode'=>'payment',
            'cancel_url'=>'http://127.0.0.1:8000/pay/cancel',
            'success_url'=> 'http://127.0.0.1:8000/pay/success',
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR']
            ],
            'metadata'=>[

            ]
        ]);

        $this->redirectUrl = $session->url;
    }

    public function getStripeRedirectUrl(){
        return $this->redirectUrl;
    }     
}

















