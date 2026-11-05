<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Panier stocké en session : [id produit => quantité].
 * Les clients n'ont pas de compte, le panier vit le temps de la session du navigateur.
 */
final class Cart
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
    ) {
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->raw();
        $this->set($product, ($items[$product->getId()] ?? 0) + $quantity);
    }

    /** Une quantité de 0 retire le produit ; la quantité est plafonnée au stock disponible. */
    public function set(Product $product, int $quantity): void
    {
        $items = $this->raw();
        $id = (int) $product->getId();

        if (null !== $product->getStock()) {
            $quantity = min($quantity, $product->getStock());
        }

        if ($quantity > 0 && $product->isActive()) {
            $items[$id] = $quantity;
        } else {
            unset($items[$id]);
        }

        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * Lignes du panier avec les produits chargés depuis la base.
     * Les produits supprimés ou désactivés depuis l'ajout sont ignorés.
     *
     * @return list<array{product: Product, quantity: int, totalCents: int}>
     */
    public function getLines(): array
    {
        $items = $this->raw();
        if ([] === $items) {
            return [];
        }

        $lines = [];
        foreach ($this->products->findBy(['id' => array_keys($items), 'active' => true]) as $product) {
            $quantity = $items[$product->getId()];
            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'totalCents' => $product->getPriceCents() * $quantity,
            ];
        }

        return $lines;
    }

    public function getTotalCents(): int
    {
        return array_sum(array_column($this->getLines(), 'totalCents'));
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return array<int, int> */
    private function raw(): array
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    /** @param array<int, int> $items */
    private function save(array $items): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $items);
    }
}
