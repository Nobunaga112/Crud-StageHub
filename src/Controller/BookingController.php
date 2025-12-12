<?php

namespace App\Controller;
date_default_timezone_set('Asia/Manila');

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\ActivityLogger;

#[Route('/booking')]
final class BookingController extends AbstractController
{
    #[Route('/', name: 'app_booking_index', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        
        // If user is admin, show all bookings
        // If user is staff, show only bookings they created (or old records without owner)
        if ($this->isGranted('ROLE_ADMIN')) {
            $bookings = $bookingRepository->findBy([], ['id' => 'ASC']);
        } else {
            // Staff can see:
            // 1. Bookings they created (createdBy = current user)
            // 2. Old bookings without owner (createdBy = null)
            $bookings = $bookingRepository->createQueryBuilder('b')
                ->where('b.createdBy = :user OR b.createdBy IS NULL')
                ->setParameter('user', $user)
                ->orderBy('b.id', 'ASC')
                ->getQuery()
                ->getResult();
        }
        
        // Add display number to each booking
        $bookingsWithDisplayNumber = [];
        $counter = 1;
        foreach ($bookings as $booking) {
            $bookingsWithDisplayNumber[] = [
                'entity' => $booking,
                'displayNumber' => $counter++,
            ];
        }
        
        return $this->render('booking/index.html.twig', [
            'bookingsWithDisplayNumber' => $bookingsWithDisplayNumber,
        ]);
    }

    #[Route('/new', name: 'app_booking_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_STAFF')]
public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
{
    $booking = new Booking();
    
    // Automatically set the current user as the creator
    $booking->setCreatedBy($this->getUser());
    
    $form = $this->createForm(BookingType::class, $booking);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Get the selected equipment
        $equipment = $booking->getEquipment();
        
        // Check if equipment is selected
        if ($equipment === null) {
            $this->addFlash('error', 'Please select equipment.');
            return $this->render('booking/new.html.twig', [
                'booking' => $booking,
                'form' => $form->createView(),
            ]);
        }
        
        // Check equipment availability
        if (!$equipment->isAvailability()) {
            $this->addFlash('error', sprintf(
                'The equipment "%s" is not available for booking. Please select available equipment.',
                $equipment->getEquipment()
            ));
            return $this->render('booking/new.html.twig', [
                'booking' => $booking,
                'form' => $form->createView(),
            ]);
        }
        
        // Save the booking
        $entityManager->persist($booking);
        $entityManager->flush();

        $activityLogger->log(
            'BOOKING_CREATED',
            sprintf(
                'Booking ID: %d, Customer: %s, Dates: %s to %s, Equipment: %s',
                $booking->getId(),
                $booking->getCustomerName(),
                $booking->getStartDate()?->format('Y-m-d'),
                $booking->getEndDate()?->format('Y-m-d'),
                $equipment->getEquipment() ?? 'Unknown'
            )
        );

        $this->addFlash('success', 'Your booking has been submitted!');
        return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('booking/new.html.twig', [
        'booking' => $booking,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_booking_show', methods: ['GET'])]
#[IsGranted('ROLE_STAFF')]
public function show(Booking $booking, BookingRepository $bookingRepository): Response
{
    // Check ownership
    $this->checkBookingOwnership($booking);
    
    // Get all bookings for display number calculation - with same permission logic as index
    $user = $this->getUser();
    if ($this->isGranted('ROLE_ADMIN')) {
        $allBookings = $bookingRepository->findBy([], ['id' => 'ASC']);
    } else {
        $allBookings = $bookingRepository->createQueryBuilder('b')
            ->where('b.createdBy = :user OR b.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    // Calculate display number for this booking
    $bookingDisplayNumber = 0;
    $counter = 1;
    foreach ($allBookings as $b) {
        if ($b->getId() === $booking->getId()) {
            $bookingDisplayNumber = $counter;
            break;
        }
        $counter++;
    }

    return $this->render('booking/show.html.twig', [
        'booking' => $booking,
        'bookingDisplayNumber' => $bookingDisplayNumber,
    ]);
}

    #[Route('/{id}/edit', name: 'app_booking_edit', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_STAFF')]
public function edit(Request $request, Booking $booking, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
{
    // Check ownership and assign owner to old records if needed
    $this->checkAndAssignBookingOwnership($booking, $entityManager);
    
    // Store original status for validation
    $originalStatus = $booking->getStatus();
    
    $form = $this->createForm(BookingType::class, $booking);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Check if trying to change from completed to active
        $newStatus = $booking->getStatus();
        
        if ($originalStatus === 'completed' && $newStatus === 'active') {
            $this->addFlash('error', 'Completed bookings cannot be changed back to active.');
            return $this->render('booking/edit.html.twig', [
                'booking' => $booking,
                'form' => $form->createView(),
            ]);
        }
        
        // Get the selected equipment
        $equipment = $booking->getEquipment();
        
        // Check if equipment is selected
        if ($equipment === null) {
            $this->addFlash('error', 'Please select equipment.');
            return $this->render('booking/edit.html.twig', [
                'booking' => $booking,
                'form' => $form->createView(),
            ]);
        }
        
        // Check equipment availability (unless it's the same equipment that was already booked)
        $originalEquipment = $entityManager->getUnitOfWork()->getOriginalEntityData($booking)['Equipment'] ?? null;
        $isSameEquipment = $originalEquipment && $originalEquipment->getId() === $equipment->getId();
        
        if (!$isSameEquipment && !$equipment->isAvailability()) {
            $this->addFlash('error', sprintf(
                'The equipment "%s" is not available for booking. Please select available equipment.',
                $equipment->getEquipment()
            ));
            return $this->render('booking/edit.html.twig', [
                'booking' => $booking,
                'form' => $form->createView(),
            ]);
        }
        
        $entityManager->flush();

        $activityLogger->log(
            'BOOKING_UPDATED',
            sprintf('Booking ID: %d, Status: %s', $booking->getId(), $booking->getStatus())
        );

        $this->addFlash('success', 'Booking updated successfully.');
        
        return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('booking/edit.html.twig', [
        'booking' => $booking,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}/delete', name: 'app_booking_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        // Check ownership and assign owner to old records if needed
        $this->checkAndAssignBookingOwnership($booking, $entityManager);
        
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->getPayload()->getString('_token'))) {

             // Store info BEFORE deletion - FIXED!
        $bookingId = $booking->getId(); // This line was missing!
        $customerName = $booking->getCustomerName(); // This line was missing!
            $entityManager->remove($booking);
            $entityManager->flush();

             // LOG: Booking Deleted
        $activityLogger->log(
            'BOOKING_DELETED',
            sprintf('Booking ID: %d, Customer: %s', $bookingId, $customerName)
        );

            
            $this->addFlash('success', 'Booking deleted successfully.');
        }

        return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * Calculate display number based on position in sorted list
     */
    private function calculateDisplayNumber(Booking $booking, array $allBookings): int
    {
        $counter = 1;
        foreach ($allBookings as $b) {
            if ($b->getId() === $booking->getId()) {
                return $counter;
            }
            $counter++;
        }
        return $booking->getId(); // Fallback to actual ID
    }
    
    /**
     * Check if current user can access this booking
     * Throws AccessDeniedException if not allowed
     */
    private function checkBookingOwnership(Booking $booking): void
    {
        $user = $this->getUser();
        
        // Admins can access everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Staff can access:
        // 1. Bookings they created
        // 2. Old bookings without owner
        $createdBy = $booking->getCreatedBy();
        
        if ($createdBy === null) {
            // Old record without owner - staff can access
            return;
        }
        
        if ($createdBy->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only access your own bookings.');
        }
    }
    
    /**
     * Check ownership and assign current user as owner if booking has no owner
     */
    private function checkAndAssignBookingOwnership(Booking $booking, EntityManagerInterface $entityManager): void
    {
        $user = $this->getUser();
        
        // Admins can access everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // If booking has no owner, assign current user as owner
        if ($booking->getCreatedBy() === null) {
            $booking->setCreatedBy($user);
            $entityManager->flush();
            return;
        }
        
        // Check if current user is the owner
        if ($booking->getCreatedBy()->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only edit/delete your own bookings.');
        }
    }
}