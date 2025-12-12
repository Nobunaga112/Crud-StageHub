<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        // Store the last username in the session (optional, for login form)
        $request->getSession()->set('_security.last_username', $username);
        
        return new Passport(
            new UserBadge($username, function ($username) {
                // Find the user
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
                
                // Check if user exists
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid credentials.');
                }
                
                // Check if user is active
                if ($user->getStatus() === 'inactive') {
                    throw new CustomUserMessageAuthenticationException(
                        'Your account has been deactivated. Please contact an administrator.'
                    );
                }
                
                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get the user from the token
        $user = $token->getUser();
        
        // Check user roles and redirect accordingly
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            // Admin goes to dashboard
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        } elseif ($this->authorizationChecker->isGranted('ROLE_STAFF')) {
            // Staff goes to booking page
            return new RedirectResponse($this->urlGenerator->generate('app_booking_index'));
        }
        
        // Default fallback (in case there are other roles)
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}