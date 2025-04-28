<?php


namespace App\DataFixtures;

use App\Entity\Product; 
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Tableau avec les données de produits, y compris description, image, stock, et taille
        $products = [
            ['name' => 'Blackbelt', 'price' => 30.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'blackbelt.jpg', 'stock' => 100, 'size' => 'L'],
            ['name' => 'BlueBelt', 'price' => 30.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'bluebelt.jpg', 'stock' => 50, 'size' => 'M'],
            ['name' => 'Street', 'price' => 34.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'street.jpg', 'stock' => 200, 'size' => 'S'],
            ['name' => 'Pokeball', 'price' => 45.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'pokeball.jpg', 'stock' => 75, 'size' => 'L'],
            ['name' => 'PinkLady', 'price' => 30.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'pinklady.jpg', 'stock' => 150, 'size' => 'S'],
            ['name' => 'Snow', 'price' => 32.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'snow.jpg', 'stock' => 80, 'size' => 'M'],
            ['name' => 'Greyback', 'price' => 28.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'greyback.jpg', 'stock' => 120, 'size' => 'L'],
            ['name' => 'BlueCloud', 'price' => 45.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'bluecloud.jpg', 'stock' => 90, 'size' => 'XL'],
            ['name' => 'BornInUsa', 'price' => 59.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'borninusa.jpg', 'stock' => 60, 'size' => 'M'],
            ['name' => 'GreenSchool', 'price' => 42.00, 'description' => 'Sweatshirt de haute qualité', 'image' => 'greenschool.jpg', 'stock' => 110, 'size' => 'S'],
        ];

        // Boucle pour insérer les produits avec description, image et stock
        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setDescription($data['description']);
            $product->setImage($data['image']);
            $product->setStock($data['stock']);
            $product->setSize($data['size']);
    
            $manager->persist($product);
        }

        $manager->flush();
    }
}




