<?php

namespace Attla\Authentic;

class StatelessToken extends \Attla\Support\AbstractData {
    /**
     * Access token
     *
     * @var string|null
     */
    private string|null $access = null;

    /**
     * Refresh token
     *
     * @var string|null
     */
    private string|null $refresh = null;

    /**
     * Revoke token
     *
     * @var string|null
     */
    private string|null $revoke = null;
}
