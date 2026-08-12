<?php
/**
 *    zahls.ch Payment Gateway - upgrade the module
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_9($module)
{
    $query = 'DELETE FROM `' . _DB_PREFIX_ . 'zahls_payment_methods` WHERE `pm` = \'sofort\'';
    Db::getInstance()->execute($query);
    return include _PS_MODULE_DIR_ . 'zahls/sql/install.php';
}
