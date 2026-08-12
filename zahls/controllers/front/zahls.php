<?php
/**
 *    zahls FrontController
 *
 * @author    zahls <support@zahls.ch>
 * @copyright zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Zahls\ZahlsException;
use Zahls\ZahlsPaymentGateway\Config\ZahlsConfig;
use Zahls\ZahlsPaymentGateway\Service\ZahlsApiService;
use Zahls\ZahlsPaymentGateway\Service\ZahlsDbService;
use Zahls\ZahlsPaymentGateway\Service\ZahlsOrderService;

class zahlszahlsModuleFrontController extends ModuleFrontController
{
    private $supportedLang = ['nl', 'fr', 'de', 'it', 'nl', 'pt', 'tr', 'pl', 'es', 'dk'];
    private $defaultLang = 'en';

    public function postProcess()
    {
        try {
            // Collect Gateway data
            if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
                $zahlsDbService = new ZahlsDbService();
                $zahlsApiService = new ZahlsApiService();
                $zahlsOrderService = new ZahlsOrderService();
            } else {
                $zahlsDbService = $this->get('zahls.zahlspaymentgateway.zahlsdbservice');
                $zahlsApiService = $this->get('zahls.zahlspaymentgateway.zahlsapiservice');
                $zahlsOrderService = $this->get('zahls.zahlspaymentgateway.zahlsorderservice');
            }
            $context = Context::getContext();

            $order = Order::getByCartId($context->cart->id);
            if (\Configuration::get('ZAHLS_CREATE_ORDER_BEFORE_PAYMENT') == 1 && !$order) {
                $pm = Tools::getValue('zahlsPaymentMethod');
                $paymentMethod = ZahlsConfig::getPaymentMethodNameByPm($pm);
                $zahlsModule = \Module::getInstanceByName('zahls');
                $zahlsModule->validateOrder(
                    $context->cart->id,
                    \Configuration::get($zahlsOrderService::PS_CHECKOUT_STATE_PENDING),
                    (float) $context->cart->getOrderTotal(true, \Cart::BOTH),
                    $paymentMethod
                );
                $order = Order::getByCartId($context->cart->id);
            }
            $cart = $context->cart;
            $customer = $context->customer;

            $invoiceAddress = new Address($cart->id_address_invoice);
            $deliveryAddress = new Address($cart->id_address_delivery);

            $billingAddress = [
                'firstname' => $invoiceAddress->firstname,
                'lastname' => $invoiceAddress->lastname,
                'company' => $invoiceAddress->company,
                'street' => $invoiceAddress->address1 . ' ' . $invoiceAddress->address2,
                'postcode' => $invoiceAddress->postcode,
                'city' => $invoiceAddress->city,
                'country' => Country::getIsoById($invoiceAddress->id_country),
                'phone' => $invoiceAddress->phone,
            ];

            $shippingAddress = [
                'firstname' => $deliveryAddress->firstname,
                'lastname' => $deliveryAddress->lastname,
                'company' => $deliveryAddress->company,
                'street' => $deliveryAddress->address1 . ' ' . $deliveryAddress->address2,
                'postcode' => $deliveryAddress->postcode,
                'city' => $deliveryAddress->city,
                'country' => Country::getIsoById($deliveryAddress->id_country),
            ];
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $currency = $context->currency->iso_code;

            $redirectUrls = [
                'success' => $context->link->getModuleLink($this->module->name, 'validation', [], true),
                'cancel' => $context->link->getModuleLink($this->module->name, 'validation', ['zahlsError' => 'cancel'], true),
                'failed' => $context->link->getModuleLink($this->module->name, 'validation', ['zahlsError' => 'fail'], true),
            ];
            $currencyIsoCode = !empty($currency) ? $currency : 'USD';

            if ($gatewayId = $zahlsDbService->getCartGatewayId($cart->id)) {
                $zahlsApiService->deleteZahlsGateway($gatewayId);
            }
            $paymentMethod = Tools::getValue('zahlsPaymentMethod');
            $pm = ($paymentMethod != 'zahls') ? [$paymentMethod] : [];

            $metaData['X-Shop-Version'] = (string) _PS_VERSION_;
            $module = Module::getInstanceByName('zahls');
            if ($module) {
                $metaData['X-Plugin-Version'] = (string) $module->version;
            }

            $lang = Language::getIsoById($context->cookie->id_lang);
            if (!in_array($lang, $this->supportedLang)) {
                $lang = $this->defaultLang;
            }

            $gateway = $zahlsApiService->createZahlsGateway(
                $total,
                $currencyIsoCode,
                $redirectUrls,
                $cart,
                $customer,
                $billingAddress,
                $shippingAddress,
                $pm,
                $metaData,
                $lang,
                $order
            );

            if (!$gateway) {
                throw new ZahlsException();
            }
            $context->cookie->paymentId = $gateway->getId();
            $zahlsDbService->insertGatewayInfo(
                $cart->id,
                $gateway->getId(),
                $paymentMethod
            );
            Tools::redirect($gateway->getLink());
        } catch (ZahlsException $e) {
            Tools::redirect(
                Context::getContext()->link->getModuleLink(
                    $this->module->name,
                    'validation',
                    [
                        'zahlsError' => 'config',
                    ],
                    true
                )
            );
        }
    }
}
