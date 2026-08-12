<?php
/**
 *    zahls.ch Payment Gateway.
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
 * @license   MIT License
 */

namespace Zahls\ZahlsPaymentGateway\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ZahlsDbService
{
    /**
     * @param int $idCart cart id
     * @param int $idGateway gateway id
     * @param string $paymentMethod Payment method
     * @return bool
     */
    public function insertGatewayInfo($idCart, $idGateway, $paymentMethod)
    {
        if (empty($idCart) || empty($idGateway)) {
            return false;
        }
        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'zahls_gateway` (`id_cart`, `id_gateway`, `pm`)
            VALUES (' . (int) $idCart . ',' . (int) $idGateway . ',\'' . $paymentMethod . '\')'
            . ' ON DUPLICATE KEY UPDATE id_gateway = ' . (int) $idGateway
            . ', pm = \'' . $paymentMethod . '\''
        );
    }

    /**
     * @param int $idCart cart id
     * @return int
     */
    public function getCartGatewayId($idCart)
    {
        if (empty($idCart)) {
            return null;
        }

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT id_gateway FROM `' . _DB_PREFIX_ . 'zahls_gateway`
            WHERE id_cart = ' . (int) $idCart
        );
    }
    /**
     * @param int $idGateway cart id
     * @return int
     */
    public function getGatewayCartId($idGateway)
    {
        if (empty($idGateway)) {
            return null;
        }

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT id_cart FROM `' . _DB_PREFIX_ . 'zahls_gateway`
            WHERE id_gateway = ' . (int) $idGateway
        );
    }

    /**
     * @param int $idCart cart id
     * @return string
     */
    public function getPaymentMethodByCartId($idCart): string
    {
        if (empty($idCart)) {
            return '';
        }

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT pm FROM `' . _DB_PREFIX_ . 'zahls_gateway`
            WHERE id_cart = ' . (int) $idCart
        );
    }
}
