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

use Exception;

function upgrade_module_1_4_8($module)
{
    try {
        $table = 'zahls_payment_methods';
        $paymentMethodSql = new DbQuery();
        $paymentMethodSql->from($table);
        $results = Db::getInstance()->executeS($paymentMethodSql);
        foreach ($results as $result) {
            $data = [];
            if (isSerialized($result['country'])) {
                $data['country'] = json_encode(unserialize($result['country']));
            }
            if (isSerialized($result['currency'])) {
                $data['currency'] = json_encode(unserialize($result['currency']));
            }
            if (isSerialized($result['customer_group'])) {
                $data['customer_group'] = json_encode(unserialize($result['customer_group']));
            }
            if (empty($data)) {
                continue;
            }
            $where = 'id = ' . (int) $result['id'];
            Db::getInstance()->update($table, $data, $where);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function isSerialized($data, $strict = true)
{
    // If it isn't a string, it isn't serialized.
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);

    if ($data === 'N;') {
        return true;
    }

    if (strlen($data) < 4) {
        return false;
    }

    if (':' !== $data[1]) {
        return false;
    }

    if ($strict) {
        $lastc = substr($data, -1);
        if ($lastc !== ';' && $lastc !== '}') {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');

        // Either ; or } must exist.
        if (false === $semicolon && false === $brace) {
            return false;
        }

        // But neither must be in the first X characters.
        if (false !== $semicolon && $semicolon < 3) {
            return false;
        }

        if (false !== $brace && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if (substr($data, -2, 1) !== '"') {
                    return false;
                }
            } elseif (!str_contains($data, '"')) {
                return false;
            }
        case 'a':
        case 'O':
        case 'E':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
}
