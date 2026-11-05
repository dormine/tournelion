<?php

namespace App\Twig;

use App\Service\Cart;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    public function __construct(private readonly Cart $cart)
    {
    }

    /** 1250 → « 12,50 € » */
    #[AsTwigFilter('price')]
    public function price(int $cents): string
    {
        return number_format($cents / 100, 2, ',', "\u{202F}")."\u{00A0}€";
    }

    #[AsTwigFunction('cart_count')]
    public function cartCount(): int
    {
        return $this->cart->count();
    }
}
