<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CategoryRepository;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\CategoryFormType;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request; 

final class CategoryController extends AbstractController
{
    #[Route('/admin/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    
    {
        $categories = $categoryRepository->findAll();
        
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
            'categories'=>$categories
        ]);
    }

    #[Route('/admin/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request):Response
    {   
        $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash(type: 'success', message: 'votre catégorie a été créée');
            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/new.html.twig',['form'=>$form->createView()]);
    }

    #[Route('/admin/category/{id}/update', name: 'app_category_update')]
    public function update(Category $category, EntityManagerInterface $entityManager, Request $request):Response

    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->flush();

            $this->addFlash(type:'success', message: 'votre catégorie a été modifiée');

            return $this->redirectToRoute(route:'app_category');
        
        }

        return $this->render('category/update.html.twig', ['form' => $form->createView()]);
    
}

#[Route('/admin/category/{id}/delete', name: 'app_category_delete')]
    public function delete(Category $category, EntityManagerInterface $entityManager):Response

    {

       $entityManager->remove($category);
       $entityManager->flush();

       $this->addFlash(type:'danger', message: 'votre catégorie a été supprimée');

       return $this->redirectToRoute(route:'app_category');
    
}
}