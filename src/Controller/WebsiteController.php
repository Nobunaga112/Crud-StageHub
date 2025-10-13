<?php

namespace App\Controller;

use App\Repository\EquipmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebsiteController extends AbstractController
{
    #[Route('/', name: 'website_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('website/home.html.twig');
    }

    #[Route('/equipment', name: 'website_equipment', methods: ['GET'])]
public function equipment(Request $request, EquipmentRepository $equipmentRepository): Response
{
    $queryBuilder = $equipmentRepository->createQueryBuilder('e');

    // ✅ Filter by category (Equipment_Type)
    $selectedCategories = $request->query->all('category');
    if (!empty($selectedCategories)) {
        $queryBuilder
            ->andWhere('e.Equipment_Type IN (:categories)')
            ->setParameter('categories', $selectedCategories);
    }

    // ✅ Filter by price range
    $min = $request->query->get('min');
    $max = $request->query->get('max');

    if ($min !== null && $min !== '') {
        $queryBuilder->andWhere('e.Price >= :min')->setParameter('min', $min);
    }
    if ($max !== null && $max !== '') {
        $queryBuilder->andWhere('e.Price <= :max')->setParameter('max', $max);
    }

    // ✅ Filter by availability
    if ($request->query->get('availability') === '1') {
        $queryBuilder->andWhere('e.Availability = 1');
    }

    // ✅ Fetch all if no filters selected
    $equipmentList = $queryBuilder->getQuery()->getResult();

    // ✅ Categories for sidebar
    $categories = [
        'Lighting',
        'Sound System',
        'Stage',
        'Visuals / LED Wall',
        'Special Effects',
        'Others',
    ];

    return $this->render('website/equipment.html.twig', [
        'categories' => $categories,
        'equipmentList' => $equipmentList,
    ]);
}

}
