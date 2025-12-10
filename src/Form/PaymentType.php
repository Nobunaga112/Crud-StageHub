<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentType extends AbstractType
{
    private $bookingRepository;
    
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get all bookings sorted by ID
        $bookings = $this->bookingRepository->findBy([], ['id' => 'ASC']);
        
        // Create choice array with display numbers
        $choices = [];
        $counter = 1;
        foreach ($bookings as $booking) {
            $price = $booking->getEquipment() ? $booking->getEquipment()->getPrice() : 0;
            $label = sprintf('#%d: %s - %s ($%s)',
                $counter,
                $booking->getCustomerName(),
                $booking->getEquipment() ? $booking->getEquipment()->getEquipment() : 'No Equipment',
                number_format($price, 2)
            );
            $choices[$label] = $booking;
            $counter++;
        }
        
        $builder
            ->add('Amount', MoneyType::class, [
                'currency' => 'PHP',
                'label' => 'Amount',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0.01',
                ],
                'constraints' => [
                    new Callback([$this, 'validateAmount']),
                ],
            ])
            ->add('Method', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'Cash',
                    'Credit Card' => 'Credit Card',
                    'Bank Transfer' => 'Bank Transfer',
                    'Check' => 'Check',
                ],
                'label' => 'Payment Method',
                'attr' => ['class' => 'form-control']
            ])
            ->add('Status', ChoiceType::class, [
                'choices' => [
                    'Paid' => 'Paid',
                    'Pending' => 'Pending',
                    'Failed' => 'Failed',
                ],
                'label' => 'Payment Status',
                'attr' => ['class' => 'form-control']
            ])
            ->add('Payment_Date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Payment Date',
                'attr' => ['class' => 'form-control datepicker']
            ])
            ->add('Booking', ChoiceType::class, [
                'choices' => $choices,
                'label' => 'Booking',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Select a booking',
            ]);
    }
    
    /**
     * Custom validation for amount
     */
    public function validateAmount($amount, ExecutionContextInterface $context)
    {
        $form = $context->getRoot();
        $payment = $form->getData();
        
        if (!$payment || !$payment->getBooking()) {
            return;
        }
        
        $booking = $payment->getBooking();
        $equipment = $booking->getEquipment();
        
        if (!$equipment) {
            return;
        }
        
        $equipmentPrice = $equipment->getPrice();
        
        if ($amount < $equipmentPrice) {
            $context->buildViolation('Payment amount cannot be lower than equipment price (${{ price }}).')
                ->setParameter('{{ price }}', number_format($equipmentPrice, 2))
                ->atPath('Amount')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}