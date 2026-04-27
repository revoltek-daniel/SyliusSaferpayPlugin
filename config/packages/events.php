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
            'sylius_admin.payment_method.update.content.form.sections.gateway_configuration.saferpay' => [
                'config' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/_gatewayConfiguration.html.twig',
                    'priority' => 0,
                ],
            ],
            'sylius_admin.payment_method.create.content.form.sections.gateway_configuration.saferpay' => [
                'config' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/_gatewayConfiguration.html.twig',
                    'priority' => 0,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.show.content.header' => [
                'breadcrumbs' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Header/_breadcrumb.html.twig',
                    'priority' => 100,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.show.content.header.title_block' => [
                'title' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Header/_headerTitle.html.twig',
                    'priority' => 0,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.show.content' => [
                'saferpay_form' => [
                    'template' => '@CommerceWeaversSyliusSaferpayPlugin/Admin/PaymentMethod/ConfigurePaymentMethods/Form/_content.html.twig',
                    'priority' => 50,
                ],
            ],
            'commerce_weavers_saferpay.admin.payment_method.configure_payment_methods.show.content.saferpay_form' => [
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
