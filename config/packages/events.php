<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) {
    $containerConfigurator->extension('sylius_twig_hooks', [
        'hooks' => [
            'sylius_admin.base#javascripts' => [
                'cw_scripts' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/_scripts.html.twig',
                    'priority' => 5,
                ],
            ],
            'sylius_admin.base#stylesheets' => [
                'cw_styles' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/_styles.html.twig',
                    'priority' => 5,
                ],
            ],
            'sylius_admin.order.show.content.sections.payments.item.actions' => [
                'saferpay_refund_transition' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/Order/Show/Payment/_refundTransition.html.twig',
                    'priority' => -100,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods' => [
                'header' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/_header.html.twig',
                    'priority' => 20,
                ],
                'content' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/_content.html.twig',
                    'priority' => 10,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.header' => [
                'title' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Header/_headerTitle.html.twig',
                    'priority' => 20,
                ],
                'breadcrumb' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Header/_breadcrumb.html.twig',
                    'priority' => 10,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.form' => [
                'content' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Form/_content.html.twig',
                    'priority' => 10,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.form.content' => [
                'allowed_payment_methods' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Form/_allowedPaymentMethods.html.twig',
                    'priority' => 20,
                ],
                'buttons' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Form/_buttons.html.twig',
                    'priority' => 10,
                ],
            ],
        ],
    ]);
};
