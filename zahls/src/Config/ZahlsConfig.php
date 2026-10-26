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
    /** zahls.ch is the only platform: api.zahls.ch, login.zahls.ch */
    const PLATFORM = 'zahls.ch';

    /** Title and description of the zahls.ch payment option in the checkout, per language */
    const TITLE = 'ZAHLS_TITLE';
    const DESCRIPTION = 'ZAHLS_DESCRIPTION';

    /** Texts of the zahls.ch payment option before 1.6.14, translatable in the back office */
    const LEGACY_TEXTS = [
        self::TITLE => 'zahls.ch Payment Methods',
        self::DESCRIPTION => 'zahls.ch payment method description',
    ];

    public static function getTextKeys(): array
    {
        return [self::TITLE, self::DESCRIPTION];
    }

    /**
     * Default title and description of the zahls.ch payment option; English for languages without own texts.
     *
     * @param string $iso language ISO code
     *
     * @return array
     */
    public static function getDefaultTexts(string $iso): array
    {
        $texts = [
            'de' => [
                'Bezahlen mit zahls.ch',
                'Sie werden zur sicheren Zahlungsseite von zahls.ch weitergeleitet und wählen dort Ihre Zahlungsart.',
            ],
            'fr' => [
                'Payer avec zahls.ch',
                'Vous serez redirigé vers la page de paiement sécurisée de zahls.ch, où vous choisirez votre moyen de paiement.',
            ],
            'it' => [
                'Paga con zahls.ch',
                'Verrà reindirizzato alla pagina di pagamento sicura di zahls.ch, dove sceglierà il metodo di pagamento.',
            ],
            'en' => [
                'Pay with zahls.ch',
                'You will be redirected to the secure zahls.ch payment page, where you choose your payment method.',
            ],
        ];
        [$title, $description] = $texts[strtolower($iso)] ?? $texts['en'];

        return [self::TITLE => $title, self::DESCRIPTION => $description];
    }

    public static function getConfigKeys(): array
    {
        return [
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
