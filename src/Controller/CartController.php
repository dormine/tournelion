<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class CartController extends AbstractController
{
    public function __construct(private readonly Cart $cart)
    {
    }

    #[Route('', name: 'cart_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('cart/show.html.twig', [
            'lines' => $this->cart->getLines(),
            'totalCents' => $this->cart->getTotalCents(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): Response
    {
        $this->assertCsrf($request, 'cart');

        if (!$product->isAvailable()) {
            $this->addFlash('error', 'Ce produit n\'est plus disponible.');

            return $this->redirectToRoute('shop_product', ['slug' => $product->getSlug()]);
        }

        $this->cart->add($product, max(1, $request->request->getInt('quantity', 1)));
        $this->addFlash('success', $product->getName().' a été ajouté au panier.');

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/modifier/{id}', name: 'cart_update', methods: ['POST'])]
    public function update(Product $product, Request $request): Response
    {
        $this->assertCsrf($request, 'cart');
        $this->cart->set($product, max(0, $request->request->getInt('quantity')));

        return $this->redirectToRoute('cart_show');
    }

    private function assertCsrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
