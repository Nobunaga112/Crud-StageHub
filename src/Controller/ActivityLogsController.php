<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity/logs')]
final class ActivityLogsController extends AbstractController
{
    #[Route('/', name: 'app_activity_logs')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        // Get filter parameters from URL
        $userFilter = $request->query->get('user');
        $actionFilter = $request->query->get('action');
        $dateFilter = $request->query->get('date');
        
        // Get ALL logs first (for filtering and getting unique values)
        $allLogs = $activityLogRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Apply filters
        $filteredLogs = $allLogs;
        
        if ($userFilter) {
            $filteredLogs = array_filter($filteredLogs, function($log) use ($userFilter) {
                return stripos($log->getUsername() ?? '', $userFilter) !== false;
            });
        }
        
        if ($actionFilter) {
            $filteredLogs = array_filter($filteredLogs, function($log) use ($actionFilter) {
                return $log->getAction() === $actionFilter;
            });
        }
        
        if ($dateFilter) {
            $filteredLogs = array_filter($filteredLogs, function($log) use ($dateFilter) {
                return $log->getCreatedAt()->format('Y-m-d') === $dateFilter;
            });
        }
        
        // Get unique values for filter dropdowns from ALL logs
        $uniqueActions = [];
        $uniqueUsers = [];
        
        foreach ($allLogs as $log) {
            $action = $log->getAction();
            $username = $log->getUsername();
            
            if ($action && !in_array($action, $uniqueActions)) {
                $uniqueActions[] = $action;
            }
            
            if ($username && !in_array($username, $uniqueUsers)) {
                $uniqueUsers[] = $username;
            }
        }
        
        sort($uniqueActions);
        sort($uniqueUsers);
        
        return $this->render('activity_logs/index.html.twig', [
            'logs' => $filteredLogs,
            'uniqueActions' => $uniqueActions,
            'uniqueUsers' => $uniqueUsers,
            'currentFilters' => [  // ADD THIS - it was missing!
                'user' => $userFilter,
                'action' => $actionFilter,
                'date' => $dateFilter,
            ],
        ]);
    }
}