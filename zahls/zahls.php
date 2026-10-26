<?php
/**
 *    zahls.ch Payment Gateway.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Zahls\ZahlsPaymentGateway\Config\ZahlsConfig;
use Zahls\ZahlsPaymentGateway\Service\ZahlsApiService;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!class_exists(ZahlsConfig::class)) {
    $autoloadLocation = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadLocation)) {
        require_once $autoloadLocation;
    }
}

class Zahls extends PaymentModule
{
    /**
     * @var int
     */
    public $is_eu_compatible;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'zahls';
        $this->tab = 'payments_gateways';
        $this->module_key = '0c4dbfccbd85dd948fd9a13d5a4add90';
        $this->version = '1.6.14';
        $this->author = 'zahls';
        $this->is_eu_compatible = 1;
        $this->ps_versions_compliancy = ['min' => '8.0'];
        $this->controllers = ['payment', 'validation', 'gateway'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'zahls.ch';
        $this->description = 'Accept payments using zahls.ch';
        $this->confirmUninstall = 'Are you sure you want to uninstall?';
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        // Install default
        if (!parent::install()
             || !$this->installDb()
             || !$this->registerHook('paymentOptions')
             || !$this->registerHook('actionFrontControllerSetMedia')
             || !$this->registerHook('actionDispatcherBefore')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Back-office translation of a text of this module (translations/<iso>.php). The file is read, not included:
     * PrestaShop merges included translation files of all languages into one global array.
     *
     * @param string $text
     * @param string $iso
     *
     * @return string|null
     */
    private function getLegacyTranslation(string $text, string $iso)
    {
        $file = _PS_MODULE_DIR_ . $this->name . '/translations/' . basename(strtolower($iso)) . '.php';
        if (!is_file($file)) {
            return null;
        }
        $pattern = '/\$_MODULE\[\'<\{' . preg_quote($this->name, '/') . '\}[^>]*>' . preg_quote($this->name, '/')
            . '_' . md5($text) . '\'\]\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/';
        if (!preg_match($pattern, (string) file_get_contents($file), $match)) {
            return null;
        }
        $translation = stripslashes($match[1]);

        return $translation !== '' && $translation !== $text ? $translation : null;
    }

    /**
     * Title or description of the zahls.ch payment option in the given language.
     *
     * @param string $key ZahlsConfig::TITLE or ZahlsConfig::DESCRIPTION
     * @param int $idLang
     *
     * @return string
     */
    public function getText(string $key, int $idLang): string
    {
        $value = Configuration::get($key, $idLang);
        $iso = (string) Language::getIsoById($idLang);
        if ($value === false) {
            // Not saved in the module settings yet (e.g. right after the upgrade to 1.6.14, or a language
            // installed later): the back-office translation of the text used before, else the default text
            $value = $this->getLegacyTranslation(ZahlsConfig::LEGACY_TEXTS[$key], $iso);
        }
        // An emptied title falls back to the default text, an emptied description hides it
        if ($value === null || ($value === '' && $key === ZahlsConfig::TITLE)) {
            $value = ZahlsConfig::getDefaultTexts($iso)[$key];
        }

        return (string) $value;
    }

    /**
     * Install DataBase table
     *
     * @return bool if install was successfull
     */
    private function installDb()
    {
        $installed = include dirname(__FILE__) . '/sql/install.php';

        return $installed;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        $config = array_merge(ZahlsConfig::getConfigKeys(), ZahlsConfig::getTextKeys(), ['ZAHLS_PLATFORM']);
        foreach ($config as $var) {
            Configuration::deleteByName($var);
        }

        // Uninstall DataBase
        if (!$this->uninstallDb()) {
            return false;
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Uninstall DataBase table
     *
     * @return bool if uninstall was successfull
     */
    private function uninstallDb()
    {
        $unInstalled = include dirname(__FILE__) . '/sql/uninstall.php';

        return $unInstalled;
    }

    public function getContent()
    {
        $this->postProcess();

        if (!$this->isRegisteredInHook('actionAdminControllerSetMedia')) {
            $this->registerHook('actionAdminControllerSetMedia');
        }

        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => 'Settings',
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => 'API Secret',
                    'name' => 'zahls_api_secret',
                    'id' => 'zahls-api-secret-input',
                    'desc' => 'Paste here your API key from the Integrations page of your    zahls dashboard.',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => 'INSTANCE NAME',
                    'name' => 'zahls_instance_name',
                    'id' => 'zahls-instance-name-input',
                    'desc' => 'INSTANCE NAME is a part of the url where you access your zahls installation.
                    https://support.ch',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => 'Look and Feel Profile Id',
                    'name' => 'zahls_look_and_feel_id',
                    'id' => 'zahls-look-and-feel-id-input',
                    'desc' => 'Enter a profile ID if you wish to use a specific Look&Feel profile.',
                ],
                [
                    'type' => 'switch',
                    'label' => 'Create Order Before Payment',
                    'name' => 'zahls_create_order_before_payment',
                    'id' => 'zahls-create-order-before-payment-input',
                    'desc' => 'If enabled, the order will be created before the payment process. 
                        Otherwise, the order will be created after the payment confirmation.',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => 'Yes',
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => 'No',
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => 'Title',
                    'name' => 'zahls_title',
                    'lang' => true,
                    'desc' => 'Name of the zahls.ch payment option in the checkout.',
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Description',
                    'name' => 'zahls_description',
                    'lang' => true,
                    'desc' => 'Shown in the checkout when the customer selects the zahls.ch payment option. Leave empty to show none.',
                ],
            ],
            'buttons' => [
                [
                    'title' => 'Connect with zahls.ch',
                    'name' => 'connect_with_platform',
                    'id' => 'connect-with-platform-button',
                    'type' => 'button',
                    'class' => 'btn btn-default pull-left',
                    'js' => 'connect()',
                ],
            ],
            'submit' => [
                'title' => 'Save',
                'class' => 'btn btn-default pull-right',
                'id' => 'save-settings-button',
            ],
        ];
        foreach (ZahlsConfig::getConfigKeys() as $configKey) {
            $fieldsValue[strtolower($configKey)] = Configuration::get($configKey);
        }
        $languages = Language::getLanguages(false);
        foreach (ZahlsConfig::getTextKeys() as $textKey) {
            foreach ($languages as $language) {
                $fieldsValue[strtolower($textKey)][(int) $language['id_lang']] =
                    $this->getText($textKey, (int) $language['id_lang']);
            }
        }
        $helper = new HelperForm();
        $helper->languages = $this->context->controller->getLanguages();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'zahls_config';
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'id_language' => $this->context->language->id,
            'back_url' => $this->context->link->getAdminLink('AdminModules')
                              . '&configure=' . $this->name
                              . '&tab_module=' . $this->tab
                              . '&module_name=' . $this->name
                              . '#paypal_params',
        ];
        $form = $helper->generateForm($fields_form) . $this->renderAdditionalPaymentMethodsList();

        return $form;
    }

    /**
     * Rendered payment method list.
     */
    protected function renderAdditionalPaymentMethodsList()
    {
        $fieldsList = [
            'active' => [
                'title' => 'Status',
                'active' => 'status',
                'type' => 'bool',
            ],
            'pm' => [
                'title' => 'Payment Method',
                'type' => 'text',
            ],
            'position' => [
                'title' => 'Sorting',
                'type' => 'text',
            ],
        ];

        $adminLinkController = Context::getContext()->link->getAdminLink('AdminZahlsPaymentMethods',
            false);
        $token = Tools::getAdminTokenLite('AdminZahlsPaymentMethods');
        $helperList = new HelperList();
        $helperList->table = 'zahls_payment_methods';
        $helperList->shopLinkType = '';
        $helperList->position_identifier = 'position';
        $helperList->simple_header = true;
        $helperList->identifier = 'id';
        $helperList->actions = ['edit'];
        $helperList->show_toolbar = false;
        $helperList->title = 'Payment Methods';
        $helperList->currentIndex = $adminLinkController;
        $helperList->token = $token;

        $content = $this->getPaymentMethodsList(false);
        $helperList->listTotal = count($content);

        return $helperList->generateList($content, $fieldsList);
    }

    /**
     * Get payment methods list.
     *
     * @param bool $filterActive
     *
     * @return array
     */
    public function getPaymentMethodsList($filterActive = false): array
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('zahls_payment_methods');
        if ($filterActive) {
            $sql->where('active = 1');
            $sql->orderBy('position');
        }

        return Db::getInstance()->ExecuteS($sql);
    }

    /**
     * Process config values.
     */
    private function postProcess()
    {
        if (!Tools::isSubmit('zahls_config')) {
            return;
        }
        $zahlsApiService = new ZahlsApiService();
        $signatureCheck = $zahlsApiService->validateSignature(
            Tools::getValue('zahls_instance_name'),
            Tools::getValue('zahls_api_secret')
        );
        if (!$signatureCheck) {
            $this->context->controller->errors[] = 'Please enter valid credentials! Try again.';

            return false;
        }
        foreach (ZahlsConfig::getConfigKeys() as $configKey) {
            $configValue = Tools::getValue(strtolower($configKey));
            Configuration::updateValue($configKey, $configValue);
        }
        foreach (ZahlsConfig::getTextKeys() as $textKey) {
            $values = [];
            foreach (Language::getLanguages(false) as $language) {
                $idLang = (int) $language['id_lang'];
                $values[$idLang] = trim((string) Tools::getValue(strtolower($textKey) . '_' . $idLang));
            }
            Configuration::updateValue($textKey, $values);
        }
        $this->context->controller->confirmations[] = 'Settings are successfully updated.';
    }

    /**
     * Add asset for Shop Front Office
     *
     * @see https://support.ch/1.7/themes/getting-started/asset-management/#without-a-front-controller-module
     *
     * @param array $params
     */
    public function hookActionFrontControllerSetMedia(array $params)
    {
        $this->context->controller->registerStylesheet(
            'zahls-payment-method-icon',
            '/modules/' . $this->name . '/views/css/custom.css'
        );
        $paymentMeans = array_column($this->getPaymentMethodsList(true), 'pm');
        if (!in_array('apple-pay', $paymentMeans) && !in_array('google-pay', $paymentMeans)) {
            return;
        }

        // Apple pay device check related js
        if (in_array('apple-pay', $paymentMeans)) {
            $this->context->controller->registerJavascript(
                'zahls-payment-method-apple-pay',
                '/modules/' . $this->name . '/views/js/applepay.js',
                [
                    'priority' => 996,
                    'position' => 'bottom',
                ]
            );
        }
        // Google pay device check related js
        if (in_array('google-pay', $paymentMeans)) {
            $this->context->controller->registerJavascript(
                'zahls-payment-method-google-pay-lib',
                'https://support.ch/gp/p/js/pay.js',
                [
                    'priority' => 996,
                    'server' => 'remote',
                    'position' => 'bottom',
                ]
            );
            $this->context->controller->registerJavascript(
                'zahls-payment-method-google-pay',
                '/modules/' . $this->name . '/views/js/googlepay.js',
                [
                    'priority' => 997,
                    'position' => 'bottom',
                ]
            );
        }
    }

    /**
     * Return payment options
     *
     * @param array $params parameters
     *
     * @return array
     */
    public function hookPaymentOptions($params)
    {
        // Additional payment methods
        $this->loadTranslationsInUi();
        $action = $this->context->link->getModuleLink($this->name, 'zahls');
        $paymentMethods = [];
        foreach ($this->getPaymentMethodsList(true) as $paymentMethod) {
            if (!$this->allowedPaymentMethodToPay($paymentMethod)) {
                continue;
            }

            $configPaymentMethods = ZahlsConfig::getPaymentMethods();
            $title = $configPaymentMethods[$paymentMethod['pm']];
            $imageSrc = $this->_path . 'views/img/cardicons/card_' .
                                    str_replace('-', '_', $paymentMethod['pm']) . '.svg';

            $paymentOption = new PaymentOption();
            $paymentOption->setAction($action);
            $paymentOption->setModuleName($this->name);
            $paymentOption->setCallToActionText($this->l($title));
            $paymentOption->setInputs(
                [
                    'pm' => [
                        'name' => 'zahlsPaymentMethod',
                        'type' => 'hidden',
                        'value' => $paymentMethod['pm'],
                    ],
                ]
            );
            $paymentOption->setLogo($imageSrc);
            if ($paymentMethod['pm'] == 'zahls') {
                $idLang = (int) $this->context->language->id;
                $paymentOption->setLogo('');
                $paymentOption->setCallToActionText($this->getText(ZahlsConfig::TITLE, $idLang));
                $description = $this->getText(ZahlsConfig::DESCRIPTION, $idLang);
                if ($description !== '') {
                    $paymentOption->setAdditionalInformation(
                        '<p>' . nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) . '</p>'
                    );
                }
            }
            $paymentMethods[] = $paymentOption;
        }

        return $paymentMethods;
    }

    /**
     * "Connect With Platform" registers the integration webhook under the legacy module
     * path /module/payrexx/gateway; dispatch it to this module's webhook controller.
     *
     * @param array $params
     */
    public function hookActionDispatcherBefore(array $params)
    {
        if (($params['controller_type'] ?? null) !== Dispatcher::FC_MODULE
            || Tools::getValue('module') !== 'payrexx'
            || strtolower((string) Tools::getValue('controller')) !== 'gateway'
            || Module::isInstalled('payrexx')
        ) {
            return;
        }
        $_GET['module'] = $this->name;
        unset($_POST['module']);
    }

    public function hookActionAdminControllerSetMedia(array $params)
    {
        // We only want to load this JS on our module's configuration page
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/connect.js');
        }
    }

    /**
     * Allowed Payment Method to pay
     *
     * @param array $paymentMethod payment method
     *
     * @return true|false
     */
    public function allowedPaymentMethodToPay(array $paymentMethod): bool
    {
        $allowedCountries = json_decode($paymentMethod['country'], true);
        $allowedCurrencies = json_decode($paymentMethod['currency'], true);
        $allowedCustomerGroups = json_decode($paymentMethod['customer_group'], true);
        if (!empty($allowedCountries) && !in_array($this->context->country->id, $allowedCountries)) {
            return false;
        }
        if (!empty($allowedCurrencies) && !in_array($this->context->currency->id, $allowedCurrencies)) {
            return false;
        }
        if (!empty($allowedCustomerGroups)
             && empty(array_intersect(
                 $this->context->customer->getGroups(),
                 $allowedCustomerGroups
             ))
        ) {
            return false;
        }

        return true;
    }

    /**
     * To load the translation texts in Ui
     * Refer: https://devdocs.prestashop-project.org/8/development/internationalization/translation/translation-tips/
     *
     * @return void
     */
    public function loadTranslationsInUi()
    {
        $this->l('zahls.ch Payment Methods');
        $this->l('Masterpass');
        $this->l('Mastercard');
        $this->l('Visa');
        $this->l('Apple Pay');
        $this->l('Maestro');
        $this->l('JCB');
        $this->l('American Express');
        $this->l('WIRpay');
        $this->l('PayPal');
        $this->l('Bitcoin');
        $this->l('Airplus');
        $this->l('Billpay');
        $this->l('Bonus card');
        $this->l('CashU');
        $this->l('Carte Bleue');
        $this->l('Diners Club');
        $this->l('Direct Debit');
        $this->l('Discover');
        $this->l('ELV');
        $this->l('iDEAL');
        $this->l('Invoice');
        $this->l('My One');
        $this->l('Paysafe Card');
        $this->l('Post Finance Pay');
        $this->l('SwissBilling');
        $this->l('TWINT');
        $this->l('Barzahlen/Viacash');
        $this->l('Bancontact');
        $this->l('GiroPay');
        $this->l('EPS');
        $this->l('Google Pay');
        $this->l('WeChat Pay');
        $this->l('Alipay');
        $this->l('Centi');
        $this->l('Heidipay');
        $this->l('Bob Invoice');
        $this->l('Purchase on Invoice');
        $this->l('Klarna');
        $this->l('Samsung Pay');
        $this->l('Pay by Bank');
        $this->l('Powerpay');
        $this->l('Purchase on Account (CembraPay)');
        $this->l('Crypto');
        $this->l('VERD.cash');
    }
}
