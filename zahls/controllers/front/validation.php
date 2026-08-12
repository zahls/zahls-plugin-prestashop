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
use Zahls\ZahlsPaymentGateway\Service\ZahlsDbService;

class zahlsValidationModuleFrontController extends ModuleFrontController
{
    const ERROR_CONFIG = 'config';
    const ERROR_CANCEL = 'cancel';
    const ERROR_FAIL = 'fail';

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
        } else {
            $zahlsDbService = $this->get('zahls.zahlspaymentgateway.zahlsdbservice');
        }
        $gatewayId = $this->context->cookie->paymentId;
        $cartId = $zahlsDbService->getGatewayCartId($gatewayId);

        // Redirect to success page if successful order already exists
        $order = Order::getByCartId($cartId);
        if ($order && in_array($order->current_state, [2, 9, 10])) {
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
