<?php

/**
 * The ecrPairing request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v2.0.10
 */
namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\EcrPairing as EcrPairingResponse;

class EcrPairing extends Base
{
    protected ?string $pairingCode = null;
    protected ?string $cashierName = null;
    protected ?string $serialNumber = null;

    public function setSerialNumber(string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setPairingCode(string $pairingCode): void
    {
        $this->pairingCode = $pairingCode;
    }

    public function getPairingCode(): ?string
    {
        return $this->pairingCode;
    }

    public function setCashierName(string $cashierName): void
    {
        $this->cashierName = $cashierName;
    }

    public function getCashierName(): ?string
    {
        return $this->cashierName;
    }

    public function getResponseModel(): object
    {
        return new EcrPairingResponse();
    }

    public function getPath(): string
    {
        return 'ecr/' . $this->serialNumber . '/';
    }
}