<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;



final class HomePageController extends AbstractController
{

    #[Route('/', name: 'app_home_page', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
{
    $products = $productRepository->findBy([
        'id' =>  [1, 6, 9, 13]

    ]);
    return $this->render('homePage/index.html.twig', [
        'products' => $products,
    ]);
}

    #[Route('/home/page/product/{id}/show', name: 'app_home_page_product_show', methods: ['GET'])]
    public function show(Product $product, ProductRepository $productRepository, Request $request): Response
    {
        $size = $request->query->get('size');

        $lastProducts = $productRepository->findBy([],['id' =>'DESC'],limit: 5);
        return $this ->render('homePage/show.html.twig',[
            'product'=>$product,
            'products' =>$lastProducts,
            'size' => $size, 
            
        ]);
    }

    #[Route('/products', name: 'app_products', methods: ['GET'])]
public function products(ProductRepository $productRepository): Response
{
    $products = $productRepository->findBy([], ['id' => 'DESC']); 

    return $this->render('productsPage/index.html.twig', [
        'products' => $products, 
    ]);
}

    public function register():Response

    {
        return $this ->render(view: 'registration/register.html.twig');
    }

    public function login():Response

    {
        return $this ->render(view: 'security/login.html.twig');
    }

    public function productid():Response

    {
        return $this ->render(view: 'productidPage/index.html.twig');
    }

    public function cart():Response

    {
        return $this ->render(view: 'cart/index.html.twig');
    }

    public function admin():Response

    {
        return $this ->render(view: 'adminPage/index.html.twig');
    }

}
