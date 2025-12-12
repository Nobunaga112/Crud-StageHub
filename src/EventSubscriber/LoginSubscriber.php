<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if ($user instanceof User) {
            $this->activityLogger->log(
                'USER_LOGIN',
                sprintf(
                    'User ID: %d, Username: %s, Role: %s',
                    $user->getId(),
                    $user->getUsername(),
                    implode(', ', $user->getRoles())
                )
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        
        if ($user instanceof User) {
            $this->activityLogger->log(
                'USER_LOGOUT',
                sprintf(
                    'User ID: %d, Username: %s',
                    $user->getId(),
                    $user->getUsername()
                )
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
            LogoutEvent::class => 'onLogout',  // Add this line
        ];
    }
}