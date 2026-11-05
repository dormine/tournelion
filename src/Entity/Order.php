<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Référence lisible communiquée au client, ex. TL-20261105-4F2A. */
    #[ORM\Column(length: 32, unique: true)]
    private string $reference;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $stripeSessionId = null;

    /** Renseignés par Stripe une fois le paiement effectué. */
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerName = null;

    /** @var array<string, string|null>|null */
    #[ORM\Column(nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column]
    private int $totalCents = 0;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->reference = 'TL-'.$this->createdAt->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    }

    public function __toString(): string
    {
        return $this->reference;
    }

    public function addItem(Product $product, int $quantity): void
    {
        $this->items->add(new OrderItem($this, $product, $quantity));
        $this->totalCents += $product->getPriceCents() * $quantity;
    }

    /** Appelé par le webhook Stripe ; renvoie false si la commande était déjà payée. */
    public function markPaid(): bool
    {
        if (OrderStatus::Pending !== $this->status) {
            return false;
        }
        $this->status = OrderStatus::Paid;
        $this->paidAt = new \DateTimeImmutable();

        foreach ($this->items as $item) {
            $item->getProduct()?->decreaseStock($item->getQuantity());
        }

        return true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    /** @return array<string, string|null>|null */
    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    /** @param array<string, string|null>|null $shippingAddress */
    public function setShippingAddress(?array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }
}
