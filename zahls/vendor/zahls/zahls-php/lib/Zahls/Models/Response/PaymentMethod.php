<?php

/**
 * PaymentMethod response model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.7.5
 */

namespace Zahls\Models\Response;

/**
 * PaymentMethod class
 *
 * @package Zahls\Models\Response
 */
class PaymentMethod extends \Zahls\Models\Request\PaymentMethod
{
    protected string $name;

    protected array $label;

    protected array $logo;

    protected array $options_by_psp;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): array
    {
        return $this->label;
    }

    public function setLabel(array $label): void
    {
        $this->label = $label;
    }

    public function getLogo(): array
    {
        return $this->logo;
    }

    public function setLogo(array $logo): void
    {
        $this->logo = $logo;
    }

    public function getoptions_by_psp(): array
    {
        return $this->options_by_psp;
    }

    public function setoptions_by_psp(array $options_by_psp): void
    {
        $this->options_by_psp = $options_by_psp;
    }
}
