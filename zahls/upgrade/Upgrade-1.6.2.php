<?php
/**
 *    zahls.ch Payment Gateway - upgrade the module
 *
 * @author    zahls <support@zahls.ch>
 * @copyright zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_2($module)
{
    return include _PS_MODULE_DIR_ . 'zahls/sql/install.php';
}
