<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, Security $security, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->logger = $logger;
    }
    
    public function log(string $action, ?string $targetData = null): void
    {
        $user = $this->security->getUser();
        
        $log = new ActivityLog();
        $log->setAction($action);
        $log->setTargetData($targetData);
        // createdAt is automatically set in the constructor
        
        if ($user instanceof User) {
            // Store user info directly (NO RELATION - just store the values)
            $log->setUserId($user->getId());
            $log->setUsername($user->getUsername());
            $log->setUserRole(implode(', ', $user->getRoles()));
        } else {
            // For anonymous actions (like before login)
            $log->setUserId(null);
            $log->setUsername('Anonymous');
            $log->setUserRole('ANONYMOUS');
        }

        try {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            // Log the exception so the developer can inspect why activity logging failed
            $this->logger->error('Failed to write activity log', [
                'action' => $action,
                'targetData' => $targetData,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}