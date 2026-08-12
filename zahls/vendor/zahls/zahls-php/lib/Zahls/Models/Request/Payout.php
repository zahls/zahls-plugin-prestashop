<?php

/**
 * The Payout request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.8.4
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\Payout as ResponsePayout;

/**
 * Class Payout
 *
 * @package Zahls\Models\Request
 */
class Payout extends Base
{
    protected string $currency;

    protected int $amount;

    protected int $pspId;

    protected ?string $statementDescriptor;

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getPspId(): int
    {
        return $this->pspId;
    }

    public function setPspId(int $pspId): void
    {
        $this->pspId = $pspId;
    }

    public function getStatementDescriptor(): ?string
    {
        return $this->statementDescriptor;
    }

    public function setStatementDescriptor(?string $statementDescriptor): void
    {
        $this->statementDescriptor = $statementDescriptor;
    }

    public function getResponseModel(): ResponsePayout
    {
        return new ResponsePayout();
    }
}
