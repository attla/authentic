<?php

namespace Attla\Authentic;

use Attla\Token\Factory as Token;
use Attla\Cookier\Facade as Cookier;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpFoundation\Request;

class Parser
{
    /**
     * The request instance
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The key name
     *
     * @var string
     */
    protected $name = 'Authorization';

    /**
     * The header prefix
     *
     * @var string
     */
    protected $prefix = 'bearer';

    /**
     * Create a new authentication guard
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request, $name = null)
    {
        $this->request = $request;
        !empty($name) && $this->name = $name;
    }

    /**
     * Attempt to parse the token from some other possible headers
     *
     * @return null|string
     */
    protected function fromAltHeaders()
    {
        return $this->request->server->get('HTTP_AUTHORIZATION')
            ?: $this->request->server->get('REDIRECT_HTTP_AUTHORIZATION', '');
    }

    /**
     * Try retrieve the token from the request header
     *
     * @return null|string
     */
    public function fromHeader()
    {
        $header = $this->request->header($this->name) ?: $this->fromAltHeaders();

        if ($header && ($position = strripos($header, $this->prefix)) !== false) {
            $header = substr($header, $position + strlen($this->prefix));

            return trim(
                strpos($header, ',') !== false ? strstr($header, ',', true) : $header
            );
        }

        return null;
    }

    /**
     * Get the token for the current request
     *
     * @return string|null
     */
    public function get()
    {
        $token = $this->request->query($this->name);
        if (empty($token)) {
            $token = $this->request->input($this->name);
        }

        if (empty($token)) {
            $token = $this->fromHeader();
        }

        if (empty($token)) {
            $token = $this->fromCookie();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token ?: '';
    }

    /**
     * Try retrieve the token from the request cookie
     *
     * @return null|string
     */
    public function fromCookie()
    {
        return Cookier::get($this->name);
    }

    /**
     * Get the currently authenticated user
     *
     * @return \Attla\Token\Parser
     */
    public function parse()
    {
        return $token = Token::parse($this->get())
            // ->ip()
            // ->browser()
            ->issuedBy($this->request->getHttpHost())
            ->permittedFor($this->request->getHttpHost())
            ->associative();
    }

    /**
     * Create a auth token
     *
     * @return \Attla\Token\Creator
     */
    public function create(Authenticatable $user, $expiration = 7200)
    {
        return Token::create()
            ->issuedBy($this->request->getHttpHost())
            ->issuedAt($time = time())
            ->expiresAt($time + $expiration)
            // ->ip()
            // ->browser()
            ->body($user);
    }

    /**
     * Set the key prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the key name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the current request instance
     *
     * @param \Illuminate\Http\Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }
}
