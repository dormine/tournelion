<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne de commande. Le nom et le prix sont copiés au moment de l'achat :
 * modifier ou supprimer le produit ensuite ne change pas les anciennes commandes.
 */
#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Product $product;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column]
    private int $unitPriceCents;

    #[ORM\Column]
    private int $quantity;

    public function __construct(Order $order, Product $product, int $quantity)
    {
        $this->order = $order;
        $this->product = $product;
        $this->productName = $product->getName();
        $this->unitPriceCents = $product->getPriceCents();
        $this->quantity = $quantity;
    }

    public function __toString(): string
    {
        return $this->quantity.' × '.$this->productName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity;
    }
}
