<?php
/**
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://support.ch for more information.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright zahls
 * @license   MIT License
 */

namespace Zahls\ZahlsPaymentGateway\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Zahls\Models\Request\SignatureCheck;
use Zahls\Models\Response\Gateway;
use Zahls\Models\Response\Transaction;
use Zahls\ZahlsException;
use Zahls\ZahlsPaymentGateway\Util\BasketUtil;

class ZahlsApiService
{
    private $instanceName;
    private $apiKey;
    private $platform;

    public function __construct()
    {
        $this->instanceName = \Configuration::get('ZAHLS_INSTANCE_NAME');
        $this->apiKey = \Configuration::get('ZAHLS_API_SECRET');
        $this->platform = \Configuration::get('ZAHLS_PLATFORM') ?: 'zahls.ch';
    }

    /**
     * @param int $gatewayId
     *
     * @return Gateway|null
     */
    public function getzahls.chGateway($gatewayId): ?Gateway
    {
        if (!$gatewayId) {
            return null;
        }

        $zahls = $this->getInterface($this->instanceName, $this->apiKey, $this->platform);
        $gateway = new \Zahls\Models\Request\Gateway();
        $gateway->setId($gatewayId);

        try {
            return $zahls->getOne($gateway);
        } catch (ZahlsException $e) {
        }
        return null;
    }

    public function getTransactionByGateway($zahlsGateway): ?Transaction
    {
        if (!in_array($zahlsGateway->getStatus(), [Transaction::CONFIRMED, Transaction::WAITING])) {
            return null;
        }
        $invoices = $zahlsGateway->getInvoices();

        if (!$invoices || !$invoice = end($invoices)) {
            return null;
        }

        if (!$transactions = $invoice['transactions']) {
            return null;
        }

        return $this->getzahls.chTransaction(end($transactions)['id']);
    }

    /**
     * @param int $transactionId
     *
     * @return Transaction|null
     */
    public function getzahls.chTransaction($transactionId): ?Transaction
    {
        if (!$transactionId) {
            return null;
        }

        $zahls = $this->getInterface($this->instanceName, $this->apiKey, $this->platform);
        $transaction = new \Zahls\Models\Request\Transaction();
        $transaction->setId($transactionId);

        try {
            return $zahls->getOne($transaction);
        } catch (ZahlsException $e) {
            return null;
        }
    }

    public function createzahls.chGateway(
        float $total,
        string $currency,
        array $redirectUrls,
        $cart,
        $customer,
        array $billingAddress,
        array $shippingAddress,
        array $pm,
        array $metaData,
        string $lang,
        $order
    ): ?Gateway {
        $basket = BasketUtil::createBasketByCart($cart);
        $basketAmount = BasketUtil::getBasketAmount($basket);
        $purpose = BasketUtil::createPurposeByCart($cart);

        $zahls = $this->getInterface($this->instanceName, $this->apiKey, $this->platform);

        $gateway = new \Zahls\Models\Request\Gateway();

        // Fallback for basket feature
        if ((int) $basketAmount === (int) ($total * 100)) {
            $gateway->setBasket($basket);
        } else {
            $gateway->setPurpose($purpose);
        }

        $gateway->setAmount((int) (string) ($total * 100));
        $gateway->setCurrency($currency);
        $gateway->setSuccessRedirectUrl($redirectUrls['success']);
        $gateway->setCancelRedirectUrl($redirectUrls['cancel']);
        $gateway->setFailedRedirectUrl($redirectUrls['failed']);
        $gateway->setPsp([]);
        $gateway->setPm($pm);
        $gateway->setReferenceId($cart->id);
        $gateway->setSkipResultPage(true);
        $gateway->setValidity(15);

        if (\Configuration::get('ZAHLS_LOOK_AND_FEEL_ID')) {
            $gateway->setLookAndFeelProfile(\Configuration::get('ZAHLS_LOOK_AND_FEEL_ID'));
        }

        $gateway->addField('title', '');
        $gateway->addField('forename', $customer->firstname);
        $gateway->addField('surname', $customer->lastname);
        $gateway->addField('company', $customer->company);
        $gateway->addField('street', $billingAddress['street']);
        $gateway->addField('postcode', $billingAddress['postcode']);
        $gateway->addField('place', $billingAddress['city']);
        $gateway->addField('country', $billingAddress['country']);
        $gateway->addField('phone', $billingAddress['phone']);
        $gateway->addField('email', $customer->email);
        $gateway->addField('custom_field_1', $cart->id, 'Cart ID');

        if ($order && $order->id) {
            $gateway->addField('custom_field_2', $order->id, 'Order ID');
        }

        $gateway->addField('delivery_forename', $shippingAddress['firstname']);
        $gateway->addField('delivery_surname', $shippingAddress['lastname']);
        $gateway->addField('delivery_company', $shippingAddress['company']);
        $gateway->addField('delivery_street', $shippingAddress['street']);
        $gateway->addField('delivery_postcode', $shippingAddress['postcode']);
        $gateway->addField('delivery_place', $shippingAddress['city']);
        $gateway->addField('delivery_country', $shippingAddress['country']);

        if (!empty($lang)) {
            $gateway->setLanguage($lang);
        }

        try {
            $zahls->setHttpHeaders($metaData);
            return $zahls->create($gateway);
        } catch (ZahlsException $e) {
        }
        return null;
    }

    public function deletezahls.chGateway($gatewayId)
    {
        $zahls = $this->getInterface($this->instanceName, $this->apiKey, $this->platform);

        $gateway = new \Zahls\Models\Request\Gateway();
        $gateway->setId($gatewayId);

        try {
            $zahls->delete($gateway);
        } catch (ZahlsException $e) {
        }
    }

    /**
     * Get zahls object
     *
     * @param string $instance
     * @param string $apiKey
     * @param string $platform
     * @return    zahls
     */
    private function getInterface(string $instance, string $apiKey, string $platform): \Zahls\Zahls
    {
        return new \Zahls\Zahls($instance, $apiKey, '', $platform);
    }

    /**
     * validate the api signature
     *
     * @param string $instance
     * @param string $apiKey
     * @param string $platform
     * @return true|false
     */
    public function validateSignature(string $instance, string $apiKey, string $platform): bool
    {
        $zahls = $this->getInterface($instance, $apiKey, $platform);
        try {
            $zahls->getOne(new SignatureCheck());
            return true;
        } catch (ZahlsException $e) {
            return false;
        }
    }
}
