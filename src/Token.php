<?php

namespace Attla\Authentic;

use Attla\Token\Factory;
use Attla\Cookier\Facade as Cookier;
use Attla\Support\Envir;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Str;

class Token
{
    /**
     * The request instance
     *
     * @var \Illuminate\Http\Request
     */
    protected static $request;

    /**
     * The key name
     *
     * @var string
     */
    protected static $name = 'Authorization';

    /**
     * The header prefix
     *
     * @var string
     */
    protected static $prefix = 'bearer';

    /**
     * Attempt to parse the token from some other possible headers
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string|null
     */
    protected static function fromAltHeaders(Request $request = null)
    {
        $request ??= static::$request;
        return $request->server->get('HTTP_AUTHORIZATION')
            ?: $request->server->get('REDIRECT_HTTP_AUTHORIZATION', '');
    }

    /**
     * Try retrieve the token from the request header
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string|null
     */
    public static function fromHeader(Request $request = null)
    {
        $request ??= static::$request;
        $header = $request->header(static::$name) ?: static::fromAltHeaders($request);

        if ($header && ($position = strripos($header, static::$prefix)) !== false) {
            $header = substr($header, $position + strlen(static::$prefix));

            return trim(
                strpos($header, ',') !== false ? strstr($header, ',', true) : $header
            );
        }

        return null;
    }

    /**
     * Try retrieve the token from the request cookie
     *
     * @return string|null
     */
    public static function fromCookie()
    {
        return Cookier::get(static::$name);
    }

    /**
     * Get the token for the current request
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string
     */
    public static function fromRequest(Request $request = null)
    {
        $request ??= static::$request;
        $token = $request->query(static::$name);
        if (empty($token)) {
            $token = $request->input(static::$name);
        }

        if (empty($token)) {
            $token = static::fromHeader($request);
        }

        if (empty($token)) {
            $token = static::fromCookie();
        }

        if (empty($token)) {
            $token = $request->getPassword();
        }

        return $token ?: '';
    }

    /**
     * Get the currently authenticated user
     *
     * @param string $token
     * @return \Attla\Token\Parser
     */
    public static function parse(string $token)
    {
        return Factory::parse($token)
            // ->ip()
            // ->browser()
            ->issuedBy(static::host(Envir::getConfig('authentic.flow.server')) ?: static::$request->getHttpHost())
            ->permittedFor(static::$request->getHttpHost())
            ->associative();
    }

    /**
     * Create a auth token
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param int $expiration
     * @return \Attla\Token\Creator
     */
    public static function create(Authenticatable $user, $expiration = 7200)
    {
        return Factory::create()
            ->issuedBy(static::$request->getHttpHost())
            ->permittedFor(static::$request->getHttpHost())
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
     * @return void
     */
    public static function setPrefix($prefix)
    {
        static::$prefix = $prefix;
    }

    /**
     * Set the key name
     *
     * @param string $name
     * @return void
     */
    public static function setName($name)
    {
        static::$name = $name;
    }

    /**
     * Set the current request instance
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public static function setRequest(Request $request)
    {
        static::$request = $request;
    }


    /**
     * Format a host
     *
     * @param string $url
     * @return string
     */
    public static function host($url)
    {
        if (!$url) {
            return '';
        }

        $url = (string) $url;
        if (!Str::startsWith($url, 'http')) {
            $url = 'http://' . $url;
        }

        $port = parse_url($url, PHP_URL_PORT);
        return parse_url($url, PHP_URL_HOST) . ($port ? ':' . $port : '');
    }
}
