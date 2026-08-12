<?php

/**
 * The SignatureCheck request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.5.0
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\SignatureCheck as ResponseSignatureCheck;

/**
 * Class SignatureCheck
 *
 * @package Zahls\Models\Request
 */
class SignatureCheck extends Base
{
    public function getResponseModel(): ResponseSignatureCheck
    {
        return new ResponseSignatureCheck();
    }
}
