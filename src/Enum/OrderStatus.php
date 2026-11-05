<?php

namespace App\Enum;

enum OrderStatus: string
{
    /** Commande créée, le client est parti payer chez Stripe. */
    case Pending = 'pending';
    /** Paiement confirmé par le webhook Stripe. */
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de paiement',
            self::Paid => 'Payée',
            self::Shipped => 'Expédiée',
            self::Cancelled => 'Annulée',
        };
    }
}
