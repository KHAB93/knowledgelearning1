<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getCart(SessionInterface $session): array
    {
        // Récupère le panier depuis la session, ou un tableau vide si aucun panier n'est trouvé
        $cart = $session->get('cart', []);
        $cartWithData = [];

        // Remplir les données du panier avec les produits et les quantités
        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) { 
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            }
        }

        // Calcul du total du panier
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        // Retourne un tableau avec les items et le total
        return [
            'items' => $cartWithData, // Utiliser 'items' ici pour correspondre à ton code
            'total' => $total
        ];
    }
}
