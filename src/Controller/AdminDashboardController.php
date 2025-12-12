<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Repository\PaymentRepository;
use App\Repository\EquipmentRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        UserRepository $userRepository,
        BookingRepository $bookingRepository,
        PaymentRepository $paymentRepository,
        EquipmentRepository $equipmentRepository,
        ActivityLogRepository $activityLogRepository
    ): Response
    {
        // Get user statistics
        $totalUsers = $userRepository->count([]);
        $totalAdmins = $userRepository->countByRole('ROLE_ADMIN');
        $totalStaff = $userRepository->countByRole('ROLE_STAFF');
        
        // Get equipment statistics
        $totalEquipment = $equipmentRepository->count([]);
        
        // Get booking statistics
        $totalBookings = $bookingRepository->count([]);
        $activeBookings = $bookingRepository->countByStatus('active');
        $completedBookings = $bookingRepository->countByStatus('completed');
        
        // Get payment statistics
        $totalPayments = $paymentRepository->count([]);
        $totalRevenue = $paymentRepository->getTotalRevenue();
        
        // Get recent activity logs (last 10 activities)
        $recentActivities = $activityLogRepository->findRecentActivities(10);
        
        // Format activities for display
        $formattedActivities = [];
        foreach ($recentActivities as $log) {
            $formattedActivities[] = [
                'id' => $log->getId(),
                'type' => $this->determineTypeFromAction($log->getAction()),
                'date' => $log->getCreatedAt(),
                'title' => $this->generateLogTitle($log),
                'description' => $this->generateLogDescription($log),
                'user' => $log->getUsername() ?? 'System',
                'user_role' => $log->getUserRole() ?? 'Unknown',
                'status' => 'completed', // Default status since your entity doesn't have status field
                'action' => $log->getAction(),
                'target_data' => $log->getTargetData()
            ];
        }

        return $this->render('admin_dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalEquipment' => $totalEquipment,
            'totalBookings' => $totalBookings,
            'activeBookings' => $activeBookings,
            'completedBookings' => $completedBookings,
            'totalPayments' => $totalPayments,
            'totalRevenue' => $totalRevenue,
            'recentActivities' => $formattedActivities,
        ]);
    }
    
    private function determineTypeFromAction(?string $action): string
    {
        if (!$action) {
            return 'general';
        }
        
        $action = strtolower($action);
        
        // Booking related actions
        if (str_contains($action, 'booking') || str_contains($action, 'book') || 
            str_contains($action, 'reserve') || str_contains($action, 'schedule')) {
            return 'booking';
        }
        
        // Payment related actions
        if (str_contains($action, 'payment') || str_contains($action, 'pay') || 
            str_contains($action, 'invoice') || str_contains($action, 'revenue')) {
            return 'payment';
        }
        
        // User related actions
        if (str_contains($action, 'user') || str_contains($action, 'login') || 
            str_contains($action, 'logout') || str_contains($action, 'register') ||
            str_contains($action, 'profile')) {
            return 'user';
        }
        
        // Equipment related actions
        if (str_contains($action, 'equipment') || str_contains($action, 'item') || 
            str_contains($action, 'inventory') || str_contains($action, 'gear')) {
            return 'equipment';
        }
        
        return 'general';
    }
    
    private function generateLogTitle($log): string
    {
        $action = $log->getAction() ?? 'Action performed';
        $username = $log->getUsername() ?? 'System';
        
        return ucfirst($username) . ' ' . $action;
    }
    
    private function generateLogDescription($log): string
    {
        $action = $log->getAction() ?? 'performed action';
        $username = $log->getUsername() ?? 'System';
        $userRole = $log->getUserRole() ?? 'User';
        $targetData = $log->getTargetData();
        
        $description = ucfirst($username) . " ($userRole) $action";
        
        // Add target data if available and not too long
        if ($targetData) {
            $decodedData = json_decode($targetData, true);
            if ($decodedData) {
                // Try to extract meaningful information
                if (isset($decodedData['entityType'])) {
                    $description .= " on " . $decodedData['entityType'];
                    if (isset($decodedData['entityId'])) {
                        $description .= " #" . $decodedData['entityId'];
                    }
                } elseif (isset($decodedData['target'])) {
                    $description .= " on " . $decodedData['target'];
                }
            } else {
                // If not JSON, just show truncated version
                if (strlen($targetData) > 50) {
                    $description .= " (Data: " . substr($targetData, 0, 50) . "...)";
                } else {
                    $description .= " (Data: $targetData)";
                }
            }
        }
        
        return $description;
    }
}