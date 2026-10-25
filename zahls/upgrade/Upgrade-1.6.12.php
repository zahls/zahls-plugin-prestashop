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

function upgrade_module_1_6_12($module)
{
    // The platform setting is gone: zahls.ch is the only platform
    Configuration::deleteByName('ZAHLS_PLATFORM');

    return true;
}
