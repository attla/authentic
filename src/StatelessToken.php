<?php

namespace Attla\Authentic;

use Illuminate\Contracts\Auth\Authenticatable;

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

    /**
     * Create a stateless token by user
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param int $expiration
     * @return static
     */
    public static function fromUser(Authenticatable $user, $expiration = 7200)
    {
        return new static([
            'access' => Token::create($user, $expiration)->get(),
        ]);
    }
}
