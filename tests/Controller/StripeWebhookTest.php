<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StripeWebhookTest extends WebTestCase
{
    private const SECRET = 'whsec_test_secret';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testWebhookWithInvalidSignatureIsRejected(): void
    {
        $this->client->request('POST', '/stripe/webhook', server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=faux'], content: '{}');

        self::assertResponseStatusCodeSame(400);
    }

    public function testCompletedSessionMarksOrderPaidOnce(): void
    {
        $product = (new Product())->setName('Mimosa')->setSlug('mimosa')->setPriceCents(2500)->setStock(3);
        $order = new Order();
        $order->addItem($product, 2);
        $order->setStripeSessionId('cs_test_123');
        $this->em->persist($product);
        $this->em->persist($order);
        $this->em->flush();

        $this->sendEvent('checkout.session.completed', [
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'customer_details' => ['email' => 'client@example.com', 'name' => 'Camille'],
            'collected_information' => ['shipping_details' => [
                'name' => 'Camille Dupont',
                'address' => ['line1' => '1 rue des Mimosas', 'postal_code' => '06000', 'city' => 'Nice', 'country' => 'FR'],
            ]],
        ]);
        self::assertResponseIsSuccessful();
        self::assertEmailCount(2);

        // Stripe peut rejouer l'événement : ni double décrément du stock, ni double mail.
        $this->sendEvent('checkout.session.completed', ['id' => 'cs_test_123', 'object' => 'checkout.session', 'payment_status' => 'paid']);
        self::assertEmailCount(0);

        $this->em->clear();
        $order = $this->em->getRepository(Order::class)->findOneBy(['stripeSessionId' => 'cs_test_123']);
        self::assertSame(OrderStatus::Paid, $order->getStatus());
        self::assertSame('client@example.com', $order->getEmail());
        self::assertSame('Camille Dupont', $order->getCustomerName());
        self::assertSame('Nice', $order->getShippingAddress()['city']);
        self::assertSame(1, $order->getItems()->first()->getProduct()->getStock());
    }

    /** @param array<string, mixed> $session */
    private function sendEvent(string $type, array $session): void
    {
        $payload = json_encode(['id' => 'evt_test', 'object' => 'event', 'type' => $type, 'data' => ['object' => $session]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, self::SECRET);

        $this->client->request('POST', '/stripe/webhook', server: [
            'HTTP_STRIPE_SIGNATURE' => "t=$timestamp,v1=$signature",
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);
    }
}
