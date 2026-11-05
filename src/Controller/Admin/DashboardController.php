<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_order_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Tournelion · Administration')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fa fa-receipt');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-palette');
        yield MenuItem::section();
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-globe', '/');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out');
    }
}
