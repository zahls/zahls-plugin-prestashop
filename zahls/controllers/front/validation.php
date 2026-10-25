<?php
/**
 *    zahls Validation Module FrontController
 *
 * @author    zahls <support@support.ch>
 * @copyright 2023    zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
use Zahls\Models\Response\Transaction;
use Zahls\ZahlsPaymentGateway\Config\ZahlsConfig;
use Zahls\ZahlsPaymentGateway\Service\ZahlsApiService;
use Zahls\ZahlsPaymentGateway\Service\ZahlsDbService;
use Zahls\ZahlsPaymentGateway\Service\ZahlsOrderService;

class zahlsValidationModuleFrontController extends ModuleFrontController
{
    const ERROR_CONFIG = 'config';
    const ERROR_CANCEL = 'cancel';
    const ERROR_FAIL = 'fail';
    const RETURN_ATTEMPTS = 5;

    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        if (Tools::getIsset('zahlsError')) {
            $this->handleError(Tools::getValue('zahlsError'));
            exit;
        }

        if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
            $zahlsDbService = new ZahlsDbService();
            $zahlsApiService = new ZahlsApiService();
            $zahlsOrderService = new ZahlsOrderService();
        } else {
            $zahlsDbService = $this->get('zahls.zahlspaymentgateway.zahlsdbservice');
            $zahlsApiService = $this->get('zahls.zahlspaymentgateway.zahlsapiservice');
            $zahlsOrderService = $this->get('zahls.zahlspaymentgateway.zahlsorderservice');
        }
        $gatewayId = (int) $this->context->cookie->paymentId;
        $cartId = $zahlsDbService->getGatewayCartId($gatewayId);
        if (!$cartId && $this->context->cart && $this->context->cart->id) {
            // Cookie without the gateway (e.g. lost on the way back): use the customer's cart
            $cartId = (int) $this->context->cart->id;
            $gatewayId = (int) $zahlsDbService->getCartGatewayId($cartId);
        }
        if (!$cartId || !$gatewayId) {
            $this->handleError(self::ERROR_CONFIG);
        }

        // zahls redirects the customer right after the payment, usually before its webhook arrived:
        // create the order from the transaction confirmed by zahls. The confirmation can lag a few
        // seconds behind the redirect (e.g. TWINT), so look again before giving up.
        $order = Order::getByCartId($cartId);
        for ($attempt = 0; !$order && $attempt < self::RETURN_ATTEMPTS; ++$attempt) {
            if ($attempt > 0) {
                sleep(2);
                $order = Order::getByCartId($cartId);
            }
            if (!$order) {
                $order = $this->createOrderFromGateway($gatewayId, $cartId, $zahlsApiService, $zahlsDbService, $zahlsOrderService);
            }
        }

        // Redirect to success page if successful order already exists
        $successStates = [
            (int) Configuration::get(ZahlsOrderService::PS_STATUS_PAYMENT),
            (int) Configuration::get(ZahlsOrderService::PS_STATUS_BANKWIRE),
            (int) Configuration::get('PS_OS_OUTOFSTOCK_PAID'),
            (int) Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
        ];
        if ($order && in_array((int) $order->current_state, $successStates, true)) {
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart=' . $cartId .
                '&id_module=' . $this->module->id .
                '&id_order=' . $order->id .
                '&key=' . $order->secure_key
            );
        }

        $this->handleError(self::ERROR_CONFIG);
    }

    /**
     * @return Order|null
     */
    private function createOrderFromGateway($gatewayId, $cartId, $zahlsApiService, $zahlsDbService, $zahlsOrderService)
    {
        $gateway = $zahlsApiService->getZahlsGateway($gatewayId);
        if (!$gateway || (int) $gateway->getReferenceId() !== (int) $cartId) {
            return null;
        }
        $transaction = $zahlsApiService->getTransactionByGateway($gateway);
        if (!$transaction || !in_array($transaction->getStatus(), [Transaction::CONFIRMED, Transaction::WAITING], true)) {
            return null;
        }

        $zahlsDbService->lockCart($cartId);
        try {
            $order = Order::getByCartId($cartId);
            if (!$order) {
                $paymentMethod = ZahlsConfig::getPaymentMethodNameByPm($zahlsDbService->getPaymentMethodByCartId($cartId));
                $zahlsOrderService->createOrder(
                    $cartId,
                    $zahlsOrderService->getPrestaStatusByZahlsStatus($transaction->getStatus()),
                    $transaction->getAmount(),
                    $paymentMethod,
                    ['transaction_id' => $transaction->getId()]
                );
                $order = Order::getByCartId($cartId);
            }
        } finally {
            $zahlsDbService->unlockCart($cartId);
        }

        return $order ?: null;
    }

    /**
     * @param $zahlsError
     * @return void
     */
    private function handleError($zahlsError)
    {
        switch ($zahlsError) {
            case self::ERROR_CONFIG:
                $errMsg = $this->module->l('The connection to the payment provider failed. Please contact the Shop owner');
                break;
            case self::ERROR_CANCEL:
                $errMsg = $this->module->l('The transaction was cancelled. Please try again');
                break;
            case self::ERROR_FAIL:
            default:
                $errMsg = $this->module->l('The transaction failed. Please try again');
                break;
        }

        $this->errors[] = $errMsg;
        $this->redirectWithNotifications('index.php?controller=order&step=1');
    }
}
