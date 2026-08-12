<?php
/**
 * zahls.ch Payment Gateway config
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2026 zahls
 * @license   MIT License
 */
namespace Zahls\ZahlsPaymentGateway\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ZahlsConfig
{
    public static function getPlatforms(): array
    {
        return [
            'zahls.ch' => 'zahls.ch',
        ];
    }

    public static function getConfigKeys(): array
    {
        return [
            'ZAHLS_PLATFORM',
            'ZAHLS_API_SECRET',
            'ZAHLS_INSTANCE_NAME',
            'ZAHLS_LOOK_AND_FEEL_ID',
            'ZAHLS_CREATE_ORDER_BEFORE_PAYMENT',
        ];
    }

    public static function getPaymentMethods(): array
    {
        return [
            'zahls' => 'zahls.ch Payment Methods',
            'masterpass' => 'Masterpass',
            'mastercard' => 'Mastercard',
            'visa' => 'Visa',
            'apple-pay' => 'Apple Pay',
            'maestro' => 'Maestro',
            'jcb' => 'JCB',
            'american-express' => 'American Express',
            'wirpay' => 'WIRpay',
            'paypal' => 'PayPal',
            'bitcoin' => 'Bitcoin',
            'klarna' => 'Klarna',
            'airplus' => 'Airplus',
            'billpay' => 'Billpay',
            'bonuscard' => 'Bonus card',
            'cashu' => 'CashU',
            'cb' => 'Carte Bleue',
            'diners-club' => 'Diners Club',
            'sepa-direct-debit' => 'Direct Debit',
            'discover' => 'Discover',
            'elv' => 'ELV',
            'ideal' => 'iDEAL',
            'invoice' => 'Invoice',
            'myone' => 'My One',
            'paysafecard' => 'Paysafe Card',
            'post-finance-pay' => 'Post Finance Pay',
            'swissbilling' => 'SwissBilling',
            'twint' => 'TWINT',
            'barzahlen' => 'Barzahlen/Viacash',
            'bancontact' => 'Bancontact',
            'giropay' => 'GiroPay',
            'eps' => 'EPS',
            'google-pay' => 'Google Pay',
            'wechat-pay' => 'WeChat Pay',
            'alipay' => 'Alipay',
            'centi' => 'Centi',
            'heidipay' => 'Heidipay',
            'bob-invoice' => 'Bob Invoice',
            'bank-transfer' => 'Purchase on Invoice',
            'samsung-pay' => 'Samsung Pay',
            'pay-by-bank' => 'Pay by Bank',
            'powerpay' => 'Powerpay',
            'cembrapay' => 'Purchase on Account (CembraPay)',
            'crypto' => 'Crypto',
            'verd-cash' => 'VERD.cash',
        ];
    }

    public static function getPaymentMethodNameByPm(string $pm): string
    {
        $paymentMethods = self::getPaymentMethods();
        if (empty($pm) || $pm === 'zahls' || !isset($paymentMethods[$pm])) {
            return 'zahls.ch';
        }
        return $paymentMethods[$pm] . ' by zahls.ch';
    }
}
