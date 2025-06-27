<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getCart(SessionInterface $session): array
    {
         // Retrieves the basket from the session, or an empty array if no basket is found
        $cart = $session->get('cart', []);
        $cartWithData = [];

        // Fill basket data with products and quantities
        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) { 
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            }
        }

        // Calculate basket total
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        // Returns a table with items and total
        return [
            'items' => $cartWithData, // Use 'items' here to match your code
            'total' => $total
        ];
    }
}
