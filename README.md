# Symfony Knowledge Learning

Un projet Symfony pour gérer des entités comme les utilisateurs, les commandes (orders), les villes, etc. Utilise Doctrine ORM et les fixtures pour la génération de données de test.

## Installation

Clone le dépôt et installe les dépendances :


git clone https://github.com/ton-utilisateur/symfony-knowledgelearning1.git
cd symfony-knowledgelearning1
composer install




## Configuration de la base de données

Crée la base de données et exécute les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

##  Chargement des fixtures

Pour charger les données de test (utilisateurs, villes, commandes, etc.) :

```bash
php bin/console doctrine:fixtures:load
```

> Cela effacera toutes les données existantes dans la base de données.

## 🧭 Lancer le serveur


symfony server:start


Accède ensuite à l'application via [http://localhost:8000](http://localhost:8000)

##  Structure des Fixtures

- `UserFixtures` : crée des utilisateurs.
- `CityFixtures` : crée des villes.
- `OrderFixtures` : crée des commandes et relie les utilisateurs et les villes.

Assure-toi que les références (`addReference()`) sont définies dans les bonnes classes et que l’ordre de chargement respecte les dépendances.

## Contribution

Les contributions sont les bienvenues !

1. Fork ce dépôt
2. Crée ta branche (`git checkout -b feature/ma-feature`)
3. Commit tes modifications (`git commit -am 'Ajout d'une nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/ma-feature`)
5. Ouvre une pull request




