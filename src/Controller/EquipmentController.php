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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

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

    #[Route(name: 'app_equipment_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('equipment/index.html.twig', [
            'equipment' => $this->equipmentRepository->findAll(),
        ]);
    }

    // REMOVED: websiteDashboard() method since we now have separate AdminDashboardController

    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, ActivityLogger $activityLogger): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();

             $activityLogger->log(
        'EQUIPMENT_CREATED',
        sprintf(
            'Equipment ID: %d, Name: %s, Type: %s, Available: %s, Price: ₱%.2f',
            $equipment->getId(),
            $equipment->getEquipment(),
            $equipment->getEquipmentType(),
            $equipment->isAvailability() ? 'Yes' : 'No',  // Use isAvailability() for boolean
            $equipment->getPrice() ?? 0.00
        )
    );
            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_equipment_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
public function show(Equipment $equipment): Response
{
    // Get all equipment to find the position
    $allEquipment = $this->equipmentRepository->findAll();
    $position = null;
    
    foreach ($allEquipment as $index => $item) {
        if ($item->getId() === $equipment->getId()) {
            $position = $index + 1; // +1 because loop.index starts at 1
            break;
        }
    }
    
    return $this->render('equipment/show.html.twig', [
        'equipment' => $equipment,
        'equipmentDisplayNumber' => $position,
    ]);
}

    #[Route('/{id}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Equipment $equipment, ActivityLogger $activityLogger): Response
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

             $activityLogger->log(
                'EQUIPMENT_UPDATED',
                sprintf(
                    'Equipment ID: %d, Name: %s',
                    $equipment->getId(),
                    $equipment->getEquipment()
                )
            );

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Equipment $equipment, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->getPayload()->getString('_token'))) {
            $equipmentId = $equipment->getId();
    $equipmentName = $equipment->getEquipment();
    $equipmentType = $equipment->getEquipmentType();
    $equipmentPrice = $equipment->getPrice();
    
    $this->entityManager->remove($equipment);
    $this->entityManager->flush();

    $activityLogger->log(
        'EQUIPMENT_DELETED',
        sprintf(
            'Equipment ID: %d, Name: %s, Type: %s, Price: ₱%.2f',
            $equipmentId,
            $equipmentName,
            $equipmentType,
            $equipmentPrice ?? 0.00
        )
    );

        }

        return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
    }
}