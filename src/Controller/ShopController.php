<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/boutique')]
final class ShopController extends AbstractController
{
    #[Route('', name: 'shop_index')]
    public function index(ProductRepository $products): Response
    {
        return $this->render('shop/index.html.twig', [
            'products' => $products->findActive(),
        ]);
    }

    #[Route('/{slug}', name: 'shop_product')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product): Response
    {
        if (!$product->isActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('shop/show.html.twig', ['product' => $product]);
    }
}
