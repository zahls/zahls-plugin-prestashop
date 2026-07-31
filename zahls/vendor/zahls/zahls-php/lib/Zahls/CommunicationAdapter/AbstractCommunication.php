<?php

/**
 * This class is a template for all communication handler classes.
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.0
 */

namespace Zahls\CommunicationAdapter;

/**
 * Class AbstractCommunication
 * @package Zahls\CommunicationAdapter
 */
abstract class AbstractCommunication
{
    /**
     * Perform an API request
     */
    abstract public function requestApi(
        string $apiUrl,
        array $params = [],
        string $method = 'POST',
        array $httpHeader = []
    ): array;
}
