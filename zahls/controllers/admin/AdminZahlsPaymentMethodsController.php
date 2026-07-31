<?php
/**
 *    zahls.ch Payment Gateway.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
 * @license   MIT License
 */
include_once _PS_MODULE_DIR_ . 'zahls/src/Models/ZahlsPaymentMethod.php';

use Zahls\ZahlsPaymentGateway\Config\ZahlsConfig;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminZahlsPaymentMethodsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        // Set variables
        $this->table = 'zahls_payment_methods';
        $this->className = 'ZahlsPaymentMethod';
        // Enable bootstrap
        $this->bootstrap = true;
        $this->_orderBy = 'id';
        $this->_defaultOrderWay = 'DESC';
        $this->_defaultOrderBy = 'position';
        $this->identifier = 'id';
        $this->position_identifier = 'position';

        $this->id_lang = $this->context->language->id;
        $this->default_form_language = $this->context->language->id;

        // Read & update record
        $this->addRowAction('details');
        $this->addRowAction('edit');

        $paymentMethod = $this->loadObject(true);
        foreach (['country', 'currency', 'customer_group'] as $fieldName) {
            $this->fields_value[$fieldName . '[]'] = json_decode($paymentMethod->$fieldName, true);
        }
        $configPaymentMethods = ZahlsConfig::getPaymentMethods();
        $pageTitle = $this->trans($configPaymentMethods[$paymentMethod->pm]);
        $this->fields_form = [
            'legend' => [
                'title' => $pageTitle,
                'icon' => 'icon-list-ul',
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => 'Active',
                    'name' => 'active',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Yes', [], 'Admin.Global'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('No', [], 'Admin.Global'),
                        ],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Accept payments from country',
                    'name' => 'country[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'desc' => 'Leave empty accepts payment from all countries',
                    'options' => [
                        'query' => Country::getCountries($this->context->language->id),
                        'id' => 'id_country',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Payments Currency',
                    'name' => 'currency[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'desc' => 'Leave empty accepts payment for all currency',
                    'options' => [
                        'query' => Currency::getCurrencies(false, false),
                        'id' => 'id_currency',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Customer Group',
                    'name' => 'customer_group[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'desc' => 'Leave empty accepts payment for all customer groups',
                    'options' => [
                        'query' => Group::getGroups($this->context->language->id),
                        'id' => 'id_group',
                        'name' => 'name',
                    ],
                ],
                [
                    'name' => 'position',
                    'type' => 'text',
                    'label' => 'Sorting',
                    'placeholder' => 'Sorting order',
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
                'name' => 'submit_form_pm',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function display()
    {
        // show saved messages
        if ($this->context->cookie->__isset('redirect_errors')
            && $this->context->cookie->__get('redirect_errors') != '') {
            $this->errors = array_merge(
                [
                    $this->context->cookie->__get('redirect_errors'),
                ],
                $this->errors
            );
            // delete old messages
            $this->context->cookie->__unset('redirect_errors');
        }
        parent::display();
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (!Tools::isSubmit('submit_form_pm')) {
            parent::postProcess();
            return;
        }

        // Country
        $postCountry = !Tools::getIsset('country') ? [] : Tools::getValue('country');
        $_POST['country'] = json_encode($postCountry);

        // currency
        $postCurrency = !Tools::getIsset('currency') ? [] : Tools::getValue('currency');
        $_POST['currency'] = json_encode($postCurrency);

        $postCustomerGroup = !Tools::getIsset('customer_group') ? [] : Tools::getValue('customer_group');
        $_POST['customer_group'] = json_encode($postCustomerGroup);

        parent::postProcess();
    }

    /**
     * {@inheritdoc}
     */
    public function renderList()
    {
        parent::renderList();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        // Redirect to the Module page
        if (Tools::getValue('conf') == 3 || Tools::getValue('conf') == 1 || Tools::getValue('conf') == 4) {
            Tools::redirect(Context::getContext()->link->getAdminLink('AdminModules', true) . '&configure=zahls&conf=' . Tools::getValue('conf'));
        }
        parent::initContent();
    }
}
