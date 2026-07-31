<?php

/**
 * The Zahls client API basic class file
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.0
 */

namespace Zahls;

use Zahls\Models\Base;

/**
 * All interactions with the API can be done with an instance of this class.
 *
 * @package Zahls
 */
class Zahls
{
    public const CLIENT_VERSION = '2.0.14-zahls.1';

    protected Communicator $communicator;

    /**
     * Generates an API object to use for the whole interaction with Zahls.
     *
     * @throws ZahlsException
     */
    public function __construct(
        string $instance,
        string $apiSecret,
        string $communicationHandler = '',
        string $apiBaseDomain = Communicator::API_URL_BASE_DOMAIN,
        ?string $version = null
    ) {
        $this->communicator = new Communicator(
            $instance,
            $apiSecret,
            $communicationHandler ?: Communicator::DEFAULT_COMMUNICATION_HANDLER,
            $apiBaseDomain,
            $version
        );
    }

    /**
     * This method passes the header to the request.
     * The format of the elements needs to be like this: 'Content-type: multipart/form-data'
     */
    public function setHttpHeaders(array $header): void
    {
        $this->communicator->httpHeaders = $header;
    }

    /**
     * This method returns the version of the API communicator, which is the API version used for this
     * application.
     */
    public function getVersion(): ?string
    {
        return $this->communicator->getVersion();
    }

    /**
     * This magic method is used to call any method available in a communication object.
     *
     * @throws ZahlsException The model argument is missing or the method is not implemented
     */
    public function __call(string $method, array $args): Base|array
    {
        if (!$this->communicator->methodAvailable($method)) {
            throw new ZahlsException('Method ' . $method . ' not implemented');
        }
        if (empty($args)) {
            throw new ZahlsException('Argument model is missing');
        }
        $model = current($args);
        return $this->communicator->performApiRequest($method, $model);
    }
}
