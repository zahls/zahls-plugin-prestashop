<?php
/**
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://support.ch for more information.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
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

class zahlsGatewayModuleFrontController extends ModuleFrontController
{
    /**
     * Process post values.
     */
    public function postProcess()
    {
        try {
            $this->processWebhook();
            echo 'Webhook processed successfully';
        } catch (Exception $e) {
            echo 'Webhook Error: ' . $e->getMessage();
        }
        exit; // Avoid template load error.
    }

    /**
     * Process webhook values
     */
    private function processWebhook()
    {
        if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
            $zahlsOrderService = new ZahlsOrderService();
            $zahlsDbService = new ZahlsDbService();
        } else {
            $zahlsOrderService = $this->get('zahls.zahlspaymentgateway.zahlsorderservice');
            $zahlsDbService = $this->get('zahls.zahlspaymentgateway.zahlsdbservice');
        }

        $transaction = Tools::getValue('transaction');
        $cartId = $transaction['invoice']['referenceId'];
        $requestStatus = $transaction['status'];
        $order = Order::getByCartId($cartId);

        if (!$this->validRequest($transaction, $cartId, $requestStatus)) {
            return;
        }

        if (!$prestaStatus = $zahlsOrderService->getPrestaStatusByZahlsStatus($requestStatus)) {
            return;
        }

        $pm = $zahlsDbService->getPaymentMethodByCartId($cartId);
        $paymentMethod = ZahlsConfig::getPaymentMethodNameByPm($pm);

        // Create order if transaction successful
        if (!$order && in_array($requestStatus, [Transaction::CONFIRMED, Transaction::WAITING])) {
            $zahlsOrderService->createOrder(
                $cartId,
                $prestaStatus,
                $transaction['amount'],
                $paymentMethod,
                [
                    'transaction_id' => $transaction['id'],
                ]
            );
            return;
        } else {
            $zahlsOrderService->createOrderPayment(
                $order,
                $transaction,
                $paymentMethod
            );
        }

        if ($order->module !== $this->module->name) {
            return;
        }

        // Update status if transition allowed
        if ($order && $zahlsOrderService->transitionAllowed($prestaStatus, $order->current_state)) {
            $zahlsOrderService->updateOrderStatus($prestaStatus, $order);
        }
    }

    private function validRequest($transaction, $cartId, $requestStatus): bool
    {
        // check required data
        if (!$cartId || !$requestStatus || !$transaction['id']) {
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
            $zahlsApiService = new ZahlsApiService();
        } else {
            $zahlsApiService = $this->get('zahls.zahlspaymentgateway.zahlsapiservice');
        }
        $gateway = $zahlsApiService->getZahlsGateway((int) $transaction['invoice']['paymentRequestId']);

        // Validate request by gateway ID
        if (!$gateway) {
            PrestaShopLoggerCore::addLog('GATEWAY FOR CART ID: ' . $cartId . ' NOT FOUND');
        }

        $transactionObj = $zahlsApiService->getZahlsTransaction($transaction['id']);

        $zahlsAmount = $transactionObj->getAmount();

        if (empty($zahlsAmount) || $zahlsAmount !== (int) $transaction['amount']) {
            return false;
        }

        $zahlsStatus = $transactionObj->getStatus();
        if (empty($zahlsStatus) || $zahlsStatus !== $requestStatus) {
            return false;
        }

        return true;
    }
}
