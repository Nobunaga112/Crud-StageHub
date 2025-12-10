<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
final class UserProfileController extends AbstractController
{
    #[Route('/', name: 'app_user_profile_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Create change password form
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Get the new password from the form data
            $newPassword = $data['password'];
            
            // Hash and set the new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('app_user_profile_index');
        }

        return $this->render('user_profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}