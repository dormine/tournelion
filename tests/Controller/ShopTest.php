<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShopTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testShowProductListsActiveProductsOnly(): void
    {
        $this->createProduct('Mimosa', 'mimosa');
        $this->createProduct('Ancien tirage', 'ancien', active: false);

        $this->client->request('GET', '/boutique');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.product-grid', 'Mimosa');
        self::assertSelectorTextNotContains('.product-grid', 'Ancien tirage');
    }

    public function testShowInactiveProductReturnsNotFound(): void
    {
        $this->createProduct('Ancien tirage', 'ancien', active: false);

        $this->client->request('GET', '/boutique/ancien');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAddToCartCapsQuantityToStock(): void
    {
        $this->createProduct('Mimosa', 'mimosa', stock: 2);
        $crawler = $this->client->request('GET', '/boutique/mimosa');

        $this->client->submit($crawler->selectButton('Ajouter au panier')->form(['quantity' => 5]));
        $this->client->followRedirect();

        self::assertSelectorTextContains('table.cart', 'Mimosa');
        self::assertInputValueSame('quantity', '2');
        self::assertSelectorTextContains('table.cart tfoot', '50,00');
    }

    private function createProduct(string $name, string $slug, ?int $stock = null, bool $active = true): Product
    {
        $product = (new Product())->setName($name)->setSlug($slug)->setPriceCents(2500)->setStock($stock)->setActive($active);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($product);
        $em->flush();

        return $product;
    }
}
