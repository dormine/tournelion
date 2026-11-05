<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Envoie le client payer sur la page Stripe Checkout.
 * Aucune donnée bancaire ne transite par notre serveur.
 */
final class StripeCheckout
{
    private readonly StripeClient $stripe;

    public function __construct(
        #[Autowire(env: 'STRIPE_SECRET_KEY')] string $secretKey,
        private readonly UrlGeneratorInterface $urls,
    ) {
        $this->stripe = new StripeClient($secretKey);
    }

    public function createSession(Order $order): Session
    {
        $lineItems = [];
        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $item->getUnitPriceCents(),
                    'product_data' => ['name' => $item->getProductName()],
                ],
            ];
        }

        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'client_reference_id' => $order->getReference(),
            'metadata' => ['order_reference' => $order->getReference()],
            'shipping_address_collection' => ['allowed_countries' => ['FR', 'BE', 'CH', 'LU']],
            // TODO : ajouter les frais de port, ex. 'shipping_options' => [['shipping_rate' => 'shr_...']]
            'locale' => 'fr',
            'success_url' => $this->urls->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urls->generate('cart_show', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
