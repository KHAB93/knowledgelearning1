<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\SecurityAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;  
use Symfony\Component\Mime\Email; 



class RegistrationController extends AbstractController
{
    
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dump($form->getErrors(true)); // Afficher les erreurs du formulaire

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            
                if (!$plainPassword) {
                    // Cela pourrait renvoyer un message d'erreur si le mot de passe est vide
                    $this->addFlash('error', 'Mot de passe est requis.');
                    return $this->redirectToRoute('app_register');
                }

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            
            // Générer un jeton d'activation
            $activationToken = bin2hex(random_bytes(32));  // Générer un jeton aléatoire
            $user->setActivationToken($activationToken);

            // Sauvegarder l'utilisateur sans encore activer le compte
            $entityManager->persist($user);
            $entityManager->flush();

            // Créer un email de confirmation
            $email = (new TemplatedEmail())
                ->from('no-reply@tondomaine.com') // adresse de l'expéditeur
                ->to($user->getEmail()) // adresse du destinataire
                ->subject('Confirmez votre inscription')
                ->html($this->renderView('emails/activation_email.html.twig', [
                    'token' => $activationToken,
                    'email' => $user->getEmail(),
                ]));
            

            $mailer->send($email);


             // Lancer la session de l'utilisateur après l'inscription
             $this->addFlash('success', 'Votre inscription a été réussie !');

             
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    // src/Controller/RegistrationController.php

#[Route('/activate/{token}', name: 'app_activate')]
public function activateAccount(string $token, EntityManagerInterface $entityManager): Response
{
    // Chercher l'utilisateur par son jeton d'activation
    $user = $entityManager->getRepository(User::class)->findOneBy(['activationToken' => $token]);

    if ($user === null) {
        $this->addFlash('error', 'Jeton d\'activation invalide.');
        return $this->redirectToRoute('app_register');
    }

    // Activer le compte de l'utilisateur
    $user->setIsActive(true);
    $user->setActivationToken(null);  // Supprimer le jeton d'activation une fois utilisé

    $entityManager->flush();

    // Afficher un message de succès
    $this->addFlash('success', 'Votre compte a été activé avec succès !');

    return $this->redirectToRoute('app_login');
}


    
}
