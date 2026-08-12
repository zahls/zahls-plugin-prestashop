<?php
/**
 *    zahls.ch Payment Gateway BasketUtil
 *
 * @author    zahls <support@zahls.ch>
 * @copyright 2024    zahls
 * @license   MIT License
 */
namespace Zahls\ZahlsPaymentGateway\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BasketUtil
{
    /**
     * @param Cart $cart
     * @return array
     */
    public static function createBasketByCart($cart): array
    {
        $basket = [];
        // product
        foreach ($cart->getProducts() as $product) {
            $productPrice = round($product['price_wt'] * 100, 0);
            $basketItem = [
                'name' => $product['name'],
                'description' => strip_tags($product['description_short']),
                'quantity' => $product['quantity'],
                'amount' => $productPrice,
                'sku' => $product['reference'],
            ];
            if (isset($product['rate'])) {
                $basketItem['vatRate'] = $product['rate'];
            }
            $basket[] = $basketItem;
        }

        // shipping
        if ($cart->getPackageShippingCost()) {
            $shippingAmount = $cart->getPackageShippingCost() * 100;

            try {
                $shippingVatRate = 0;
                $shippingCostWithoutTax = $cart->getPackageShippingCost(null, false) * 100;
                if ($shippingAmount !== $shippingCostWithoutTax && !empty($cart->id_carrier)) {
                    $carrier = new \Carrier($cart->id_carrier);
                    $address = \Address::initialize((int) $cart->id_address_delivery);
                    $shippingVatRate = $carrier->getTaxesRate($address);
                }
            } catch (\Exception $e) {
            }
            $basket[] = [
                'name' => 'Shipping',
                'amount' => $shippingAmount,
                'vatRate' => $shippingVatRate,
            ];
        }

        // Discount
        if ($cart->getDiscountSubtotalWithoutGifts()) {
            $discountAmount = $cart->getDiscountSubtotalWithoutGifts() * 100;

            try {
                $discountVatRate = 0;
                $discountWithoutTax = $cart->getDiscountSubtotalWithoutGifts(false) * 100;
                if ($discountAmount !== $discountWithoutTax) {
                    $taxDifference = $discountAmount - $discountWithoutTax;
                    $discountVatRate = ($taxDifference / $discountWithoutTax) * 100;
                    $discountVatRate = number_format((float) $discountVatRate, 1, '.', '');
                }
            } catch (\Exception $e) {
            }

            $basket[] = [
                'name' => 'Discount',
                'amount' => -$discountAmount,
                'vatRate' => $discountVatRate,
            ];
        }
        return $basket;
    }

    /**
     * @param array $basket
     * @return int
     */
    public static function getBasketAmount(array $basket): int
    {
        $basketAmount = 0;
        foreach ($basket as $product) {
            $basketAmount += ($product['quantity'] ?? 1) * $product['amount'];
        }
        return round($basketAmount);
    }

    /**
     * @param Cart $cart
     * @return string
     */
    public static function createPurposeByCart($cart): string
    {
        $productNames = [];
        $products = $cart->getProducts();
        foreach ($products as $product) {
            $quantity = $product['cart_quantity'] > 1 ? $product['cart_quantity'] . 'x ' : '';
            $productNames[] = $quantity . $product['name'];
        }
        return implode(', ', $productNames);
    }
}
