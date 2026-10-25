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
            $zahlsApiService = new ZahlsApiService();
        } else {
            $zahlsOrderService = $this->get('zahls.zahlspaymentgateway.zahlsorderservice');
            $zahlsDbService = $this->get('zahls.zahlspaymentgateway.zahlsdbservice');
            $zahlsApiService = $this->get('zahls.zahlspaymentgateway.zahlsapiservice');
        }

        // The webhook is unsigned: only its transaction id is used, everything else comes from the API
        $requestTransaction = $this->getTransactionData();
        if (!$requestTransaction || empty($requestTransaction['id']) || empty($requestTransaction['status'])) {
            return;
        }
        $transaction = $zahlsApiService->getZahlsTransaction((int) $requestTransaction['id']);
        if (!$transaction) {
            throw new Exception('Transaction ' . (int) $requestTransaction['id'] . ' could not be loaded from zahls');
        }
        $transactionStatus = (string) $transaction->getStatus();
        if ($transactionStatus === '' || $transactionStatus !== $requestTransaction['status']) {
            // outdated or forged status
            return;
        }

        $cartId = $this->getCartId($transaction, $requestTransaction, $zahlsDbService);
        $gateway = $cartId ? $zahlsApiService->getZahlsGateway((int) $zahlsDbService->getCartGatewayId($cartId)) : null;
        if (!$gateway || $this->isTransactionOfGateway($gateway, (int) $transaction->getId()) === false) {
            PrestaShopLogger::addLog(
                'zahls webhook: transaction ' . (int) $transaction->getId() . ' does not belong to a gateway of cart ' . $cartId,
                2
            );
            return;
        }

        if (!$prestaStatus = $zahlsOrderService->getPrestaStatusByZahlsStatus($transactionStatus)) {
            return;
        }

        $pm = $zahlsDbService->getPaymentMethodByCartId($cartId);
        $paymentMethod = ZahlsConfig::getPaymentMethodNameByPm($pm);
        $payment = [
            'id' => (int) $transaction->getId(),
            'amount' => (int) round((float) $transaction->getAmount()),
        ];

        // The customer's return (validation controller) may create the order at the same time
        $zahlsDbService->lockCart($cartId);
        try {
            $order = Order::getByCartId($cartId);

            // Create order if transaction successful
            if (!$order) {
                if (in_array($transactionStatus, [Transaction::CONFIRMED, Transaction::WAITING])) {
                    $zahlsOrderService->createOrder(
                        $cartId,
                        $prestaStatus,
                        $payment['amount'],
                        $paymentMethod,
                        [
                            'transaction_id' => $payment['id'],
                        ]
                    );
                }

                return;
            }

            if ($order->module !== $this->module->name) {
                return;
            }

            // Order created before the payment: record the payment once
            if ($transactionStatus === Transaction::CONFIRMED
                && !$zahlsDbService->hasOrderPayment($order->reference, $payment['id'])
            ) {
                $zahlsOrderService->createOrderPayment(
                    $order,
                    $payment,
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

    /**
     * The gateway carries the cart id as referenceId. Depending on the transaction type zahls returns
     * it at the top level of the transaction, in its invoice, or both.
     *
     * @param Transaction $transaction transaction loaded from the API
     * @param array $requestTransaction webhook payload
     * @param ZahlsDbService $zahlsDbService
     * @return int
     */
    private function getCartId($transaction, array $requestTransaction, $zahlsDbService): int
    {
        $invoice = $transaction->getInvoice();
        $candidates = [
            $transaction->getReferenceId(),
            is_array($invoice) ? ($invoice['referenceId'] ?? null) : null,
            $requestTransaction['referenceId'] ?? null,
            $requestTransaction['invoice']['referenceId'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if ((int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        // Last resort: the gateway the cart was paid with
        $gatewayId = is_array($invoice) ? (int) ($invoice['paymentRequestId'] ?? 0) : 0;
        $gatewayId = $gatewayId ?: (int) ($requestTransaction['invoice']['paymentRequestId'] ?? 0);

        return $gatewayId ? (int) $zahlsDbService->getGatewayCartId($gatewayId) : 0;
    }

    /**
     * @param \Zahls\Models\Response\Gateway $gateway
     * @param int $transactionId
     * @return bool|null null if the gateway does not list its transactions
     */
    private function isTransactionOfGateway($gateway, int $transactionId)
    {
        $listed = false;
        foreach ($gateway->getInvoices() ?: [] as $invoice) {
            foreach ($invoice['transactions'] ?? [] as $gatewayTransaction) {
                $listed = true;
                if ((int) ($gatewayTransaction['id'] ?? 0) === $transactionId) {
                    return true;
                }
            }
        }

        // The transaction itself was loaded from zahls by its id and names this cart
        return $listed ? false : null;
    }
}
