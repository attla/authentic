<?php

namespace Attla\Authentic;

use Attla\Support\Arr as AttlaArr;
use Illuminate\Contracts\Auth\Authenticatable;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Arr;

class AuthnProvider implements UserProvider
{
    /**
     * Hash implementation
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The instance of Eloquent user model
     *
     * @var string
     */
    protected $model;

    /**
     * The index name (GSI) to use
     *
     * @var string
     */
    protected $gsi = null;

    public function __construct(HasherContract $hasher, $model, $gsi = null)
    {
        $this->gsi    = $gsi;
        $this->model  = $model;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier
     *
     * @param mixed[] $params
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById(...$params)
    {
        $model = $this->createModel();
        return $model->find(...$params);
    }

    /**
     * Retrieve a user by the given credentials
     *
     * @param array|object $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials($credentials)
    {
        if (isset($credentials['password'])) {
            unset($credentials['password']);
        }

        if (!count($credentials)) {
            return null;
        }

        $model = $this->createModel();

        if ($this->gsi) {
            $model = $model->index($this->gsi);
        }

        $identifiers = is_array($model->getAuthIdentifierName())
            ? Arr::only($credentials, $model->getAuthIdentifierName())
            : array_filter([$credentials[$model->getAuthIdentifierName()] ?? null]);

        foreach ($identifiers as $key => $value) {
            $model = $model->keyCondition($key, '=', $value);
            unset($credentials[$key]);
        }

        foreach ($credentials as $column => $value) {
            $model = $model->filter($column, '=', $value);
        }

        try {
            $query = count($identifiers) ? 'query' : 'scan';
            return $model->$query()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Validate a user against the given credentials
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: implement multiple credentials validations
        //       password, code verify (Email, phone),
        //       data verify (email, or document), recovery code, TOTP code
        return !empty($credentials['password'])
            && $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model
     *
     * @param array|object $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel(array|object $attributes = [])
    {
        $class = '\\'.ltrim($this->model, '\\');
        return new $class(is_array($attributes) ? $attributes : AttlaArr::toArray($attributes));
    }

    // NON-IMPLEMENTED METHODs
    public function retrieveByToken($identifier, $token) {}
    public function updateRememberToken(Authenticatable $user, $token) {}
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false) {}
}
