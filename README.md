# Stubborn E-commerce - Projet Symfony

Ce projet est un site e-commerce développé en Symfony pour la marque fictive **Stubborn**, spécialisée dans les sweat-shirts.

## Table des matières
- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Tests](#tests)
- [Auteur](#auteur)

## Présentation
Ce projet est réalisé dans le cadre d'un devoir.  
Il s'agit d'un site e-commerce permettant d'acheter des sweats, de gérer un panier, et de procéder à un paiement test via **Stripe**.

## Fonctionnalités
- Authentification avec validation d'email
- Gestion des utilisateurs (clients et administrateurs)
- Liste de produits avec filtres par prix
- Page de détail produit et ajout au panier
- Panier avec suppression d’articles et paiement via Stripe (mode bac à sable)
- Back-office pour ajouter, modifier, supprimer des sweats
- Tests unitaires sur le panier et l'achat

## Technologies utilisées
- Symfony 7.2.5
- PHP 8.4.6
- MySQL / Doctrine
- Composer
- Bootstrap (ou Tailwind) pour le front-end
- Stripe API
- PHPUnit (tests)

## Installation

1. Cloner ce dépôt :
    
    git clone https://github.com/KHAB93/symfony-project4-stubborn.git
    

2. Installer les dépendances PHP :
   composer install
    npm install (si tu utilises Webpack encore)
    npm run build

3. Configurer la base de données dans `.env` :
    ```
    DATABASE_URL="mysql://root:@127.0.0.1:3306/symfony_project4?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
    ```

4. Créer la base de données :
    
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    

5. Lancer le serveur local :
    symfony server:start

6. Créer un compte Stripe et récupérer les clés API de test.

7. Configurer Stripe dans `.env` :

    STRIPE_SECRET_KEY=sk_test_...
    STRIPE_PUBLIC_KEY=pk_test_...

Ces clés doivent être emplacées par des clés valides obtenues via le site de Stripe.

## Utilisation

- Accéder au site via `http://localhost:8000`
- S'inscrire et confirmer son adresse email.
- Explorer les produits, ajouter au panier, effectuer un paiement test.
- Se connecter en tant qu'administrateur pour gérer les sweats.

## Tests

Lancer les tests PHPUnit :
php bin/phpunit


