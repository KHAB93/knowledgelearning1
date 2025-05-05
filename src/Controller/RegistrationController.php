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
            dump($form->getErrors(true)); // Display form errors

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            
                if (!$plainPassword) {
                    
                    $this->addFlash('error', 'Mot de passe est requis.');
                    return $this->redirectToRoute('app_register');
                }

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            
            // Generate an activation token
            $activationToken = bin2hex(random_bytes(32));   // Generate a random token
            $user->setActivationToken($activationToken);

             // Save user without activating account
            $entityManager->persist($user);
            $entityManager->flush();

           // Create a confirmation email
            $email = (new TemplatedEmail())
                ->from('no-reply@tondomaine.com')  // sender's address
                ->to($user->getEmail()) // recipient address
                ->subject('Confirmez votre inscription')
                ->html($this->renderView('emails/activation_email.html.twig', [
                    'token' => $activationToken,
                    'email' => $user->getEmail(),
                ]));
            

            $mailer->send($email);


            // Launch user session after registration
             $this->addFlash('success', 'Votre inscription a été réussie !');

             
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

  

#[Route('/activate/{token}', name: 'app_activate')]
public function activateAccount(string $token, EntityManagerInterface $entityManager): Response
{
    // Find user by activation token
    $user = $entityManager->getRepository(User::class)->findOneBy(['activationToken' => $token]);

    if ($user === null) {
        $this->addFlash('error', 'Jeton d\'activation invalide.');
        return $this->redirectToRoute('app_register');
    }

    // Activate user account
    $user->setIsActive(true);
    $user->setActivationToken(null);  // Delete activation token once used

    $entityManager->flush();

   // Display a success message
    $this->addFlash('success', 'Votre compte a été activé avec succès !');

    return $this->redirectToRoute('app_login');
}


    
}
