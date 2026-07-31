<?php
/**
 *    zahls.ch Payment Gateway - upgrade the module
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2023    zahls
 * @license   MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_4($module)
{
    return $module->registerHook('actionFrontControllerSetMedia');
}
