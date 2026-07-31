<?php

/**
 * This class has the definition of the API used for the communication
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.0
 */

namespace Zahls;

use Zahls\CommunicationAdapter\AbstractCommunication;
use Zahls\Models\Base;
use Zahls\Models\Request\Bill;
use Zahls\Models\Request\PaymentMethod;

/**
 * This object handles the communication with the API server
 * @package Zahls
 */
class Communicator
{
    public const VERSIONS = [1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 1.13, 1.14, 1.15];
    public const API_URL_FORMAT = 'https://api.%s/%s/%s/%s/%s';
    public const API_URL_BASE_DOMAIN = 'zahls.ch';
    public const DEFAULT_COMMUNICATION_HANDLER = '\Zahls\CommunicationAdapter\CurlCommunication';
    public const GUZZLE_COMMUNICATION_HANDLER = '\Zahls\CommunicationAdapter\GuzzleCommunication';

    protected static array $methods = [
        'create'       => 'POST',
        'charge'       => 'POST',
        'refund'       => 'POST',
        'capture'      => 'POST',
        'receipt'      => 'POST',
        'preAuthorize' => 'POST',
        'cancel'       => 'DELETE',
        'delete'       => 'DELETE',
        'update'       => 'PUT',
        'getAll'       => 'GET',
        'getOne'       => 'GET',
        'details'      => 'GET',
        'patchUpdate'  => 'PATCH',

        // --- ECR Methods (Map these to valid HTTP verbs) ---
        'pair'                  => 'POST',
        'unpair'                => 'DELETE',
        'payment'               => 'POST',
        'cancelEcrPayment'      => 'POST',
        'voidEcrPayment'        => 'POST',
        'getEcrPayment'         => 'GET',
        'getEcrPaymentMethods'  => 'GET',
    ];

    protected static array $singleReturnMethods = [
        'pair',
        'unpair',
        'getEcrPaymentMethods',
        'payment',
        'voidEcrPayment',
        'getEcrPayment',
        'cancelEcrPayment',
    ];

    protected string $instance;
    protected string $apiSecret;
    protected string $apiBaseDomain;
    protected AbstractCommunication $communicationHandler;
    protected ?string $version;
    public array $httpHeaders;

    /**
     * Generates a communicator object with a communication handler like cURL.
     *
     * @throws ZahlsException
     */
    public function __construct(
        string $instance,
        string $apiSecret,
        string $communicationHandler,
        string $apiBaseDomain,
        ?string $version = null
    ) {
        $this->instance = $instance;
        $this->apiSecret = $apiSecret;
        $this->apiBaseDomain = $apiBaseDomain;

        if ($version && in_array($version, self::VERSIONS)) {
            $this->version = $version;
        } else {
            $versions = self::VERSIONS;
            $this->version = strval(end($versions));
        }

        if (!class_exists($communicationHandler)) {
            throw new ZahlsException('Communication handler class ' . $communicationHandler . ' not found');
        }
        $this->communicationHandler = new $communicationHandler();
    }

    /**
     * Gets the version of the API used.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Perform a simple API request by method name and Request model.
     *
     * @throws ZahlsException An error occurred during the Zahls Request
     */
    public function performApiRequest(string $method, Base $model): Base|array|null
    {
        $params = $model->toArray();
        $params['instance'] = $this->instance;

        $id = $params['id'] ?? 0;
        if ($id === 0 && isset($params['uuid'])) {
            $id = $params['uuid'];
        }

        $act = match ($method) {
            'refund' => $method,
            'capture' => $method,
            'receipt' => $method,
            'preAuthorize' => $method,
            'details' => $method,
            'pair' => $method,
            'unpair' => 'pair',
            'payment' => $method,
            'cancelEcrPayment'     => 'cancel',
            'voidEcrPayment'       => 'void',
            'getEcrPaymentMethods' => 'paymentMethods',
            default => '',
        };

        $apiUrl = sprintf(self::API_URL_FORMAT, $this->apiBaseDomain, 'v' . $this->version, $params['model'], $id, $act);

        $httpMethod = $this->getHttpMethod($method) === 'PUT' && $params['model'] === 'Design'
            ? 'POST'
            : $this->getHttpMethod($method);

        $this->setDefaultHttpHeaders();
        $response = $this->communicationHandler->requestApi(
            $apiUrl,
            $params,
            $httpMethod,
            $this->httpHeaders
        );

        $convertedResponse = [];
        if (!isset($response['body']['data']) || !is_array($response['body']['data'])) {
            if (!isset($response['body']['message'])) {
                throw new ZahlsException('Zahls PHP: Configuration is wrong! Check instance name and API secret', $response['info']['http_code']);
            }

            $exception = new ZahlsException($response['body']['message'], $response['info']['http_code']);
            if (!empty($response['body']['reason'])) {
                $exception->setReason($response['body']['reason']);
            }

            throw $exception;
        }

        $data = $response['body']['data'];
        if (
            ($model instanceof PaymentMethod && $method === 'getOne') ||
            ($model instanceof Bill && $method !== 'getAll') ||
            in_array($method, self::$singleReturnMethods, true)
        ) {
            $data = [$data];
        }

        foreach ($data as $object) {
            $responseModel = $model->getResponseModel();
            $convertedResponse[] = $responseModel->fromArray($object);
        }

        if ($method !== 'getAll') {
            $convertedResponse = current($convertedResponse);
        }

        return $convertedResponse;
    }

    /**
     * Gets the HTTP method to use for a specific API method
     *
     * @throws ZahlsException
     */
    protected function getHttpMethod(string $method): string
    {
        if (!$this->methodAvailable($method)) {
            throw new ZahlsException('Method ' . $method . ' not implemented');
        }

        return self::$methods[$method];
    }

    /**
     * Checks whether a method is available and activated in methods array.
     */
    public function methodAvailable(string $method): bool
    {
        return array_key_exists($method, self::$methods);
    }

    /**
     * Sets the default HTTP headers
     */
    private function setDefaultHttpHeaders(): void
    {
        $this->httpHeaders['x-api-key'] = $this->apiSecret;
    }
}
