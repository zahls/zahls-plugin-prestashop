<?php

/**
 * The Page response model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.0
 */

namespace Zahls\Models\Response;

/**
 * Class Page
 *
 * @package Zahls\Models\Response
 */
class Page extends \Zahls\Models\Request\Page
{
    protected int $createdAt = 0;

    public function getCreatedDate(): int
    {
        return $this->createdAt;
    }

    public function setCreatedDate(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
