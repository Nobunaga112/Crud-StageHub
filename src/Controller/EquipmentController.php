<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Form\EquipmentType;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Changed base route to /admin/equipment for clarity as these are admin functions
#[Route('/admin/equipment')]
final class EquipmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EquipmentRepository $equipmentRepository;

    public function __construct(EntityManagerInterface $entityManager, EquipmentRepository $equipmentRepository)
    {
        $this->entityManager = $entityManager;
        $this->equipmentRepository = $equipmentRepository;
    }

    // --- YOUR EXISTING EQUIPMENT INDEX ACTION (for the 'Equipment' link) ---
    // Access: /admin/equipment
    #[Route(name: 'app_equipment_index', methods: ['GET'])]
    public function index(): Response // Removed EquipmentRepository injection from method as it's in constructor
    {
        return $this->render('equipment/index.html.twig', [
            'equipment' => $this->equipmentRepository->findAll(),
        ]);
    }

    // --- NEW ACTION FOR THE WEBSITE DASHBOARD (for the 'Dashboard' link) ---
    // Access: /admin/equipment/dashboard
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function websiteDashboard(): Response
    {
        // --- Dummy Data for now, as entities/repos aren't made yet ---
        // You'll replace these with actual database queries once you create
        // your User, Rental, Booking entities and their repositories.

        $totalUsers = 1250; // Placeholder
        $activeRentals = 12; // Placeholder
        $pendingRentals = 5; // Placeholder
        $completedRentals = 480; // Assuming 'isAvailable' field in Equipment entity
        $rentalSales = 89000.00; // Placeholder for a monetary value
        

        // Using real data for equipment count, as that entity/repository exists
        

        $latestBookings = [
            // Dummy data for latest bookings (will be replaced by actual Booking entity data later)
            ['id' => 'SH001', 'customer' => 'Franzu Haroldu V. Tahir', 'equipment' => 'Disco Lights (x2)', 'status' => 'Pending', 'startDate' => '2024-07-20', 'endDate' => '2024-07-22'],
            ['id' => 'SH002', 'customer' => 'Osama Franz T. Jordan', 'equipment' => 'Fog Machine', 'status' => 'Approved', 'startDate' => '2024-07-19', 'endDate' => '2024-07-20'],
            ['id' => 'SH003', 'customer' => 'Franz Lebron A. Ball', 'equipment' => 'Stage Truss', 'status' => 'Completed', 'startDate' => '2024-07-15', 'endDate' => '2024-07-17'],
        ];

        return $this->render('equipment/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'active_rentals' => $activeRentals,
            'pending_rentals' => $pendingRentals,
            'completed_rentals' => $completedRentals,
            'rental_sales' => $rentalSales,
            'latest_bookings' => $latestBookings,
            // ... pass any other dummy data needed for your dashboard design
        ]);
    }

    // --- YOUR EXISTING CRUD METHODS ---

    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response // Removed EntityManagerInterface from method as it's in constructor
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment): Response
    {
        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipment $equipment): Response // Removed EntityManagerInterface from method as it's in constructor
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment): Response // Removed EntityManagerInterface from method as it's in constructor
    {
        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($equipment);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
    }
}