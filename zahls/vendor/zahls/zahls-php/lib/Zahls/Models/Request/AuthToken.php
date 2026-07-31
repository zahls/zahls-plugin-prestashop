<?php

/**
 * The AuthToken request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.1.0
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\AuthToken as ResponseAuthToken;

/**
 * Class AuthToken
 *
 * @package Zahls\Models\Request
 */
class AuthToken extends Base
{
    protected int $userId = 0;

    /**
     * The user id of the user you want an auth token for
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the user id you would like to get an auth token for
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getResponseModel(): ResponseAuthToken
    {
        return new ResponseAuthToken();
    }
}
