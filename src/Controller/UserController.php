<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $user = new User();
        
        // Create form with is_edit = false (password required)
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password (required for new users)
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Check if password is provided for new user
            if (empty($plainPassword)) {
                $this->addFlash('error', 'Password is required for new users.');
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
            
            // Validate password strength
            if (strlen($plainPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters long.');
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
                ]);
            }
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            // Ensure roles is an array
            $roles = $form->get('roles')->getData();
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            $user->setRoles($roles);
            
            // Set timestamps
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($user);
            $entityManager->flush();

            $activityLogger->log(
                'USER_CREATED',
                sprintf(
                    'User ID: %d, Username: %s, Email: %s, Role: %s',
                    $user->getId(),
                    $user->getUsername(),
                    $user->getEmail(),
                    implode(', ', $user->getRoles())
                )
            );


            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        // Create form with is_edit = true (password optional)
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if provided and not empty
            $plainPassword = $form->get('plainPassword')->getData();
            
            if (!empty($plainPassword)) {
                // Validate password strength if provided
                if (strlen($plainPassword) < 6) {
                    $this->addFlash('error', 'Password must be at least 6 characters long.');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                    ]);
                }
                
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            // If password field is empty/blank, do nothing - keep existing password
            
            // Update roles
            $roles = $form->get('roles')->getData();
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            $user->setRoles($roles);
            
            // Update timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->flush();

            // LOG: User Updated
            $activityLogger->log(
                'USER_UPDATED',
                sprintf(
                    'User ID: %d, Username: %s, Role: %s',
                    $user->getId(),
                    $user->getUsername(),
                    implode(', ', $user->getRoles())
                )
            );

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

   #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
             $userId = $user->getId();
        $username = $user->getUsername();
        $userRole = implode(', ', $user->getRoles());
        
        $entityManager->remove($user);
        $entityManager->flush();
        
        // LOG: User Deleted
        $activityLogger->log(
            'USER_DELETED',
            sprintf('User ID: %d, Username: %s, Role: %s (deleted)', $userId, $username, $userRole)
        );
            
            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }
}