<?php

/**
 * The Zahls Exception for any exception occurred during the API process
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.0
 */

namespace Zahls;

use Exception;

/**
 * Class ZahlsException
 *
 * @package Zahls
 */
class ZahlsException extends Exception
{
    private string $reason = '';

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason = ''): void
    {
        $this->reason = $reason;
    }
}
