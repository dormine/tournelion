<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Les commandes sont créées par le site et Stripe : en admin on les consulte
 * et on ne modifie que le statut (ex. passer à « Expédiée »).
 *
 * @extends AbstractCrudController<Order>
 */
final class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('status');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence')->setDisabled();
        yield DateTimeField::new('createdAt', 'Date')->setDisabled();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_combine(
                array_map(static fn (OrderStatus $s) => $s->label(), OrderStatus::cases()),
                OrderStatus::cases(),
            ));
        yield TextField::new('customerName', 'Client')->setDisabled();
        yield EmailField::new('email', 'E-mail')->setDisabled();
        yield MoneyField::new('totalCents', 'Total')->setCurrency('EUR')->setStoredAsCents()->setDisabled();
        yield CollectionField::new('items', 'Articles')->onlyOnDetail();
        yield ArrayField::new('shippingAddress', 'Adresse de livraison')->onlyOnDetail();
        yield DateTimeField::new('paidAt', 'Payée le')->onlyOnDetail();
    }
}
