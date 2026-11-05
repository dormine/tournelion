<?php

namespace App\Controller;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stripe appelle cette URL quand un paiement aboutit ou expire.
 * À déclarer dans https://dashboard.stripe.com/webhooks :
 *   URL : https://tournelion.com/stripe/webhook
 *   Événements : checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.expired
 */
final class StripeWebhookController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
        private readonly OrderMailer $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')] private readonly string $webhookSecret,
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->headers->get('Stripe-Signature', ''),
                $this->webhookSecret,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return new Response('Signature invalide', Response::HTTP_BAD_REQUEST);
        }

        /** @var Session $session */
        $session = $event->data->object;

        match ($event->type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->handlePaid($session),
            'checkout.session.expired' => $this->handleExpired($session),
            default => null,
        };

        return new Response('ok');
    }

    private function handlePaid(Session $session): void
    {
        // Certains moyens de paiement (virement…) confirment plus tard via async_payment_succeeded.
        if ('paid' !== $session->payment_status) {
            return;
        }

        $order = $this->orders->findOneBy(['stripeSessionId' => $session->id]);
        if (null === $order) {
            $this->logger->error('Webhook Stripe : commande introuvable', ['session' => $session->id]);

            return;
        }

        $customer = $session->customer_details;
        $shipping = $session->collected_information->shipping_details ?? null;
        $order
            ->setEmail($customer?->email)
            ->setCustomerName($shipping->name ?? $customer?->name)
            ->setShippingAddress(isset($shipping->address) ? $shipping->address->toArray() : null);

        // Stripe peut renvoyer le même événement plusieurs fois : markPaid() ne fait rien la 2e fois.
        if ($order->markPaid()) {
            $this->em->flush();

            // Le paiement est enregistré : un souci d'envoi de mail ne doit pas faire échouer le webhook.
            try {
                $this->mailer->sendPaidNotifications($order);
            } catch (\Throwable $e) {
                $this->logger->error('Envoi des mails de commande impossible', ['order' => $order->getReference(), 'exception' => $e]);
            }
        }
    }

    private function handleExpired(Session $session): void
    {
        $order = $this->orders->findOneBy(['stripeSessionId' => $session->id]);
        if (null !== $order && OrderStatus::Pending === $order->getStatus()) {
            $order->setStatus(OrderStatus::Cancelled);
            $this->em->flush();
        }
    }
}
