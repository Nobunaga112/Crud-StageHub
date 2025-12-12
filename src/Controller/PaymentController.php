<?php

namespace App\Controller;
date_default_timezone_set('Asia/Manila');

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\ActivityLogger;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    #[IsGranted('ROLE_STAFF')]
    public function index(PaymentRepository $paymentRepository, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        
        // If user is admin, show all payments
        // If user is staff, show only payments they created (or old payments without owner)
        if ($this->isGranted('ROLE_ADMIN')) {
            $payments = $paymentRepository->findAll();
        } else {
            // Staff can see:
            // 1. Payments they created (createdBy = current user)
            // 2. Old payments without owner (createdBy = null)
            $payments = $paymentRepository->createQueryBuilder('p')
                ->where('p.createdBy = :user OR p.createdBy IS NULL')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        }
        
        // Get all bookings for display number calculation
        $allBookings = $bookingRepository->findBy([], ['id' => 'ASC']);
        
        // Create a map of booking ID -> display number
        $bookingDisplayMap = [];
        $counter = 1;
        foreach ($allBookings as $booking) {
            $bookingDisplayMap[$booking->getId()] = $counter++;
        }
        
        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'bookingDisplayMap' => $bookingDisplayMap,
        ]);
    }

  #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_STAFF')]
public function new(Request $request, EntityManagerInterface $entityManager, BookingRepository $bookingRepository, ActivityLogger $activityLogger): Response
{
    $payment = new Payment();
    
    // Automatically set the current user as the creator
    $payment->setCreatedBy($this->getUser());
    
    $form = $this->createForm(PaymentType::class, $payment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // CHECK: If booking already has a payment
        $booking = $payment->getBooking();
        
        if ($booking && $booking->getPayment()) {
            // This booking already has a payment!
            $this->addFlash('error', sprintf(
                'Booking #%d already has a payment (Payment ID: %d). Please edit the existing payment instead.',
                $booking->getId(),
                $booking->getPayment()->getId()
            ));
            
            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }
        
        $entityManager->persist($payment);
        $entityManager->flush();

        $activityLogger->log(
            'PAYMENT_CREATED',
            sprintf(
                'Payment ID: %d, Amount: $%s, Method: %s, Status: %s, Booking: %s',
                $payment->getId(),
                $payment->getAmount(),
                $payment->getMethod(),
                $payment->getStatus(),
                $booking ? 'Booking ID: ' . $booking->getId() : 'No booking'
            )
        );

            $this->addFlash('success', 'Payment created successfully.');
            
            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        // Get all bookings for display number calculation in form
        $allBookings = $bookingRepository->findBy([], ['id' => 'ASC']);
        $bookingOptions = [];
        $counter = 1;
        foreach ($allBookings as $booking) {
            $bookingOptions[] = [
                'entity' => $booking,
                'displayNumber' => $counter++,
            ];
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'form' => $form,
            'bookingOptions' => $bookingOptions,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', methods: ['GET'])]
#[IsGranted('ROLE_STAFF')]
public function show(Payment $payment, BookingRepository $bookingRepository, PaymentRepository $paymentRepository): Response
{
    // Check ownership
    $this->checkPaymentOwnership($payment);
    
    // Get all bookings for display number calculation
    $allBookings = $bookingRepository->findBy([], ['id' => 'ASC']);
    
    // Calculate display number for this payment's booking
    $bookingDisplayNumber = 0;
    if ($payment->getBooking()) {
        $counter = 1;
        foreach ($allBookings as $booking) {
            if ($booking->getId() === $payment->getBooking()->getId()) {
                $bookingDisplayNumber = $counter;
                break;
            }
            $counter++;
        }
    }
    
    // Get all payments for display number calculation
    // Use same logic as index to respect user permissions
    $user = $this->getUser();
    if ($this->isGranted('ROLE_ADMIN')) {
        $allPayments = $paymentRepository->findBy([], ['id' => 'ASC']);
    } else {
        $allPayments = $paymentRepository->createQueryBuilder('p')
            ->where('p.createdBy = :user OR p.createdBy IS NULL')
            ->setParameter('user', $user)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    // Calculate display number for this payment
    $paymentDisplayNumber = 0;
    $counter = 1;
    foreach ($allPayments as $pay) {
        if ($pay->getId() === $payment->getId()) {
            $paymentDisplayNumber = $counter;
            break;
        }
        $counter++;
    }

    return $this->render('payment/show.html.twig', [
        'payment' => $payment,
        'bookingDisplayNumber' => $bookingDisplayNumber,
        'paymentDisplayNumber' => $paymentDisplayNumber,
    ]);
}
    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager, BookingRepository $bookingRepository, ActivityLogger $activityLogger): Response
    {
        // Check ownership and assign owner to old records if needed
        $this->checkAndAssignPaymentOwnership($payment, $entityManager);
        
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $activityLogger->log(
            'PAYMENT_UPDATED',
            sprintf('Payment ID: %d', $payment->getId())
        );


            $this->addFlash('success', 'Payment updated successfully.');
            
            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        // Get all bookings for display number calculation in form
        $allBookings = $bookingRepository->findBy([], ['id' => 'ASC']);
        $bookingOptions = [];
        $counter = 1;
        foreach ($allBookings as $booking) {
            $bookingOptions[] = [
                'entity' => $booking,
                'displayNumber' => $counter++,
            ];
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
            'bookingOptions' => $bookingOptions,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_payment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        // Check ownership and assign owner to old records if needed
        $this->checkAndAssignPaymentOwnership($payment, $entityManager);
        
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {

             $paymentId = $payment->getId();
            $amount = $payment->getAmount();
            
            $entityManager->remove($payment);
            $entityManager->flush();

            $activityLogger->log(
            'PAYMENT_DELETED',
            sprintf('Payment ID: %d, Amount: $%s', $paymentId, $amount)
        );

            
            $this->addFlash('success', 'Payment deleted successfully.');
        }

        return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * Check if current user can access this payment
     * Throws AccessDeniedException if not allowed
     */
    private function checkPaymentOwnership(Payment $payment): void
    {
        $user = $this->getUser();
        
        // Admins can access everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Staff can access:
        // 1. Payments they created
        // 2. Old payments without owner
        $createdBy = $payment->getCreatedBy();
        
        if ($createdBy === null) {
            // Old record without owner - staff can access
            return;
        }
        
        if ($createdBy->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only access your own payments.');
        }
    }
    
    /**
     * Check ownership and assign current user as owner if payment has no owner
     */
    private function checkAndAssignPaymentOwnership(Payment $payment, EntityManagerInterface $entityManager): void
    {
        $user = $this->getUser();
        
        // Admins can access everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // If payment has no owner, assign current user as owner
        if ($payment->getCreatedBy() === null) {
            $payment->setCreatedBy($user);
            $entityManager->flush();
            return;
        }
        
        // Check if current user is the owner
        if ($payment->getCreatedBy()->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only edit/delete your own payments.');
        }
    }
}