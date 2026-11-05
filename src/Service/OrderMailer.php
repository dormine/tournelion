<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire(env: 'SHOP_EMAIL')] private readonly string $shopEmail,
    ) {
    }

    /** Confirmation au client + notification à la boutique. */
    public function sendPaidNotifications(Order $order): void
    {
        $from = new Address($this->shopEmail, 'Tournelion');

        if (null !== $order->getEmail()) {
            $this->mailer->send((new TemplatedEmail())
                ->from($from)
                ->to($order->getEmail())
                ->subject('Votre commande '.$order->getReference())
                ->htmlTemplate('email/order_confirmation.html.twig')
                ->context(['order' => $order]));
        }

        $this->mailer->send((new TemplatedEmail())
            ->from($from)
            ->to($this->shopEmail)
            ->subject('Nouvelle commande '.$order->getReference())
            ->htmlTemplate('email/order_notification.html.twig')
            ->context(['order' => $order]));
    }
}
