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

function upgrade_module_1_6_11($module)
{
    // Legacy webhook path /module/payrexx/gateway (see Zahls::hookActionDispatcherBefore)
    return $module->registerHook('actionDispatcherBefore');
}
