<?php
/**
 *    zahls.ch Payment Gateway.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
 * @license   MIT License
 */

namespace Zahls\ZahlsPaymentGateway\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ZahlsOrderService
{
    // ID 8
    const PS_STATUS_ERROR = 'PS_OS_ERROR';

    // ID 7
    const PS_STATUS_REFUND = 'PS_OS_REFUND';

    // ID 2
    const PS_STATUS_PAYMENT = 'PS_OS_PAYMENT';

    // ID 10
    const PS_STATUS_BANKWIRE = 'PS_OS_BANKWIRE';

    // ID 4
    const PS_STATUS_SHIPPING = 'PS_OS_SHIPPING';

    // ID 5
    const PS_STATUS_DELIVERED = 'PS_OS_DELIVERED';

    // ID 14
    const PS_CHECKOUT_STATE_PENDING = 'PS_CHECKOUT_STATE_PENDING';

    /**
     * @param $cartId
     * @param $prestaStatus
     * @param $amount
     * @param $paymentMethod
     * @param array $extraVars
     * @return void
     */
    public function createOrder(
        $cartId,
        $prestaStatus,
        $amount,
        $paymentMethod,
        array $extraVars = []
    ) {
        $zahlsModule = \Module::getInstanceByName('zahls');
        $cart = new \Cart($cartId);
        $customer = new \Customer($cart->id_customer);
        $statusId = (int) \Configuration::get($prestaStatus);

        $zahlsModule->validateOrder(
            (int) $cart->id,
            $statusId,
            (float) $amount / 100,
            $paymentMethod,
            null,
            $extraVars,
            (int) $cart->id_currency,
            false,
            $customer->secure_key
        );
        $context = \Context::getContext();
        $context->cart = $cart;
    }

    /**
     * @param $transactionStatus
     * @return string|null
     */
    public function getPrestaStatusByZahlsStatus($transactionStatus)
    {
        $prestaStatus = null;
        switch ($transactionStatus) {
            case \Zahls\Models\Response\Transaction::CANCELLED:
            case \Zahls\Models\Response\Transaction::DECLINED:
            case \Zahls\Models\Response\Transaction::ERROR:
            case \Zahls\Models\Response\Transaction::EXPIRED:
                $prestaStatus = self::PS_STATUS_ERROR;
                break;
            case \Zahls\Models\Response\Transaction::REFUNDED:
            case \Zahls\Models\Response\Transaction::PARTIALLY_REFUNDED:
                $prestaStatus = self::PS_STATUS_REFUND;
                break;
            case \Zahls\Models\Response\Transaction::CONFIRMED:
                $prestaStatus = self::PS_STATUS_PAYMENT;
                break;
            case \Zahls\Models\Response\Transaction::WAITING:
                $prestaStatus = self::PS_STATUS_BANKWIRE;
                break;
        }

        return $prestaStatus;
    }

    /**
     * @param $newStatus
     * @param $oldStatusId
     * @return bool|void
     */
    public function transitionAllowed($newStatus, $oldStatusId)
    {
        $refundStatusId = (int) \Configuration::get(self::PS_STATUS_REFUND);
        $newStatusId = (int) \Configuration::get($newStatus);
        if ($oldStatusId === $newStatusId && $newStatusId !== $refundStatusId) {
            return false;
        }
        $orderFinalStatuses = [
            (int) \Configuration::get(self::PS_STATUS_REFUND),
            (int) \Configuration::get(self::PS_STATUS_PAYMENT),
            (int) \Configuration::get(self::PS_STATUS_SHIPPING),
            (int) \Configuration::get(self::PS_STATUS_DELIVERED),
        ];

        switch ($newStatus) {
            case self::PS_STATUS_ERROR:
            case self::PS_STATUS_PAYMENT:
            case self::PS_STATUS_BANKWIRE:
                return !in_array($oldStatusId, $orderFinalStatuses);
            case self::PS_STATUS_REFUND:
                return in_array(
                    $oldStatusId,
                    [
                        (int) \Configuration::get(self::PS_STATUS_PAYMENT),
                        (int) \Configuration::get(self::PS_STATUS_REFUND),
                    ]
                );
        }
        return false;
    }

    /**
     * @param $prestaStatus
     * @param $order
     * @return void
     */
    public function updateOrderStatus($prestaStatus, $order)
    {
        $orderHistory = new \OrderHistory();
        $prestaStatusId = \Configuration::get($prestaStatus);

        $orderHistory->id_order = (int) $order->id;
        $orderHistory->changeIdOrderState($prestaStatusId, $order, true);
        $orderHistory->addWithemail();
    }

    /**
     * Get Gateway id from the cart id
     *
     * @param int $id_cart cart id
     * @return int
     */
    public function getCartGatewayId($id_cart)
    {
        if (empty($id_cart)) {
            return 0;
        }

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT id_gateway FROM `' . _DB_PREFIX_ . 'zahls_gateway`
            WHERE id_cart = ' . (int) $id_cart);
    }

    public function createOrderPayment($order, $transaction, $paymentMethod)
    {
        $payment = new \OrderPayment();
        $payment->order_reference = $order->reference;
        $payment->id_currency = $order->id_currency;
        $payment->amount = (float) $transaction['amount'] / 100;
        $payment->payment_method = $paymentMethod;
        $payment->transaction_id = $transaction['id'];
        $payment->conversion_rate = 1;
        $payment->add();
    }
}
