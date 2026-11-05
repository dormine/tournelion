<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Cart;
use App\Service\StripeCheckout;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    /** Crée la commande « en attente » puis redirige vers la page de paiement Stripe. */
    #[Route('/commande', name: 'checkout', methods: ['POST'])]
    public function checkout(Request $request, Cart $cart, StripeCheckout $stripe, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('checkout', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $lines = $cart->getLines();
        if ([] === $lines) {
            return $this->redirectToRoute('cart_show');
        }

        $order = new Order();
        foreach ($lines as $line) {
            if (!$line['product']->isAvailable($line['quantity'])) {
                $this->addFlash('error', 'Stock insuffisant pour « '.$line['product']->getName().' ».');

                return $this->redirectToRoute('cart_show');
            }
            $order->addItem($line['product'], $line['quantity']);
        }

        $session = $stripe->createSession($order);
        $order->setStripeSessionId($session->id);
        $em->persist($order);
        $em->flush();

        return $this->redirect((string) $session->url, Response::HTTP_SEE_OTHER);
    }

    /**
     * Page de retour après paiement. On n'y valide PAS la commande :
     * seul le webhook Stripe (signé) fait foi, cette page peut être ouverte par n'importe qui.
     */
    #[Route('/commande/merci', name: 'checkout_success', methods: ['GET'])]
    public function success(Request $request, OrderRepository $orders, Cart $cart): Response
    {
        $order = $orders->findOneBy(['stripeSessionId' => $request->query->getString('session_id')]);
        if (null === $order) {
            return $this->redirectToRoute('home');
        }

        $cart->clear();

        return $this->render('checkout/success.html.twig', ['order' => $order]);
    }
}
