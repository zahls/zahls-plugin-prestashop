<?php

/**
 * The PaymentProvider request model.
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.5.5
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\PaymentProvider as ResponsePaymentProvider;

/**
 * Class PaymentProvider
 *
 * @package Zahls\Models\Request
 */
class PaymentProvider extends Base
{
    protected string $name;
    protected array $paymentMethods;
    protected array $activePaymentMethods;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    public function setPaymentMethods(array $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function getActivePaymentMethods(): array
    {
        return $this->activePaymentMethods;
    }

    public function setActivePaymentMethods(array $activePaymentMethods): void
    {
        $this->activePaymentMethods = $activePaymentMethods;
    }

    public function getResponseModel(): ResponsePaymentProvider
    {
        return new ResponsePaymentProvider();
    }
}
