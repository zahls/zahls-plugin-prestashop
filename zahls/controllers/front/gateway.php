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
        } catch (Throwable $e) {
            // Non-2xx so that zahls retries the webhook
            http_response_code(500);
            PrestaShopLogger::addLog('zahls webhook: ' . $e->getMessage(), 3, null, 'Cart', null, true);
            echo 'Webhook Error: ' . $e->getMessage();
        }
        exit; // Avoid template load error.
    }

    /**
     * Webhook payload: "Normal (PHP-Post)" form data or JSON
     *
     * @return array|null
     */
    private function getTransactionData()
    {
        $transaction = Tools::getValue('transaction');
        if (is_array($transaction)) {
            return $transaction;
        }
        $body = json_decode((string) Tools::file_get_contents('php://input'), true);
        if (is_array($body) && isset($body['transaction']) && is_array($body['transaction'])) {
            return $body['transaction'];
        }

        return null;
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

        $transaction = $this->getTransactionData();
        if (!$transaction) {
            return;
        }
        $cartId = (int) ($transaction['invoice']['referenceId'] ?? 0);
        $requestStatus = $transaction['status'] ?? null;

        if (!$this->validRequest($transaction, $cartId, $requestStatus)) {
            return;
        }

        if (!$prestaStatus = $zahlsOrderService->getPrestaStatusByZahlsStatus($requestStatus)) {
            return;
        }

        $pm = $zahlsDbService->getPaymentMethodByCartId($cartId);
        $paymentMethod = ZahlsConfig::getPaymentMethodNameByPm($pm);

        // The customer's return (validation controller) may create the order at the same time
        $zahlsDbService->lockCart($cartId);
        try {
            $order = Order::getByCartId($cartId);

            // Create order if transaction successful
            if (!$order) {
                if (in_array($requestStatus, [Transaction::CONFIRMED, Transaction::WAITING])) {
                    $zahlsOrderService->createOrder(
                        $cartId,
                        $prestaStatus,
                        $transaction['amount'],
                        $paymentMethod,
                        [
                            'transaction_id' => $transaction['id'],
                        ]
                    );
                }

                return;
            }

            if ($order->module !== $this->module->name) {
                return;
            }

            // Order created before the payment: record the payment once
            if ($requestStatus === Transaction::CONFIRMED
                && !$zahlsDbService->hasOrderPayment($order->reference, $transaction['id'])
            ) {
                $zahlsOrderService->createOrderPayment(
                    $order,
                    $transaction,
                    $paymentMethod
                );
            }

            // Update status if transition allowed
            if ($zahlsOrderService->transitionAllowed($prestaStatus, $order->current_state)) {
                $zahlsOrderService->updateOrderStatus($prestaStatus, $order);
            }
        } finally {
            $zahlsDbService->unlockCart($cartId);
        }
    }

    private function validRequest($transaction, $cartId, $requestStatus): bool
    {
        // check required data
        if (!$cartId || !$requestStatus || empty($transaction['id'])) {
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
            $zahlsApiService = new ZahlsApiService();
        } else {
            $zahlsApiService = $this->get('zahls.zahlspaymentgateway.zahlsapiservice');
        }
        $gateway = $zahlsApiService->getZahlsGateway((int) ($transaction['invoice']['paymentRequestId'] ?? 0));

        // Validate request by gateway ID
        if (!$gateway) {
            PrestaShopLogger::addLog('GATEWAY FOR CART ID: ' . $cartId . ' NOT FOUND');
        }

        $transactionObj = $zahlsApiService->getZahlsTransaction($transaction['id']);
        if (!$transactionObj) {
            throw new Exception('Transaction ' . (int) $transaction['id'] . ' could not be loaded from zahls');
        }

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
