<?php

/**
 * The AuthToken response model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.1.0
 */

namespace Zahls\Models\Response;

/**
 * Class AuthToken
 *
 * @package Zahls\Models\Response
 */
class AuthToken extends \Zahls\Models\Request\AuthToken
{
    protected string $authToken = '';
    protected ?string $authTokenExpirationDate = null;
    protected string $link = '';

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function setAuthToken(string $authToken): void
    {
        $this->authToken = $authToken;
    }

    public function getAuthTokenExpirationDate(): ?string
    {
        return $this->authTokenExpirationDate;
    }

    public function setAuthTokenExpirationDate(?string $authTokenExpirationDate): void
    {
        $this->authTokenExpirationDate = $authTokenExpirationDate;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }
}
