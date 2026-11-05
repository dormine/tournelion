<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ProductRepository $products): Response
    {
        return $this->render('home/index.html.twig', [
            'products' => \array_slice($products->findActive(), 0, 6),
        ]);
    }
}
