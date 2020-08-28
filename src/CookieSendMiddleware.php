<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CookieSendMiddleware implements MiddlewareInterface
{
    /**
     * @var CookieManagerInterface
     */
    private CookieManagerInterface $cookies;

    /**
     * @var bool
     */
    private bool $removeResponseCookies;

    /**
     * @param CookieManagerInterface $cookies object with cookies to set in response.
     * @param bool $removeResponseCookies whether to remove previously set cookies from the response.
     */
    public function __construct(CookieManagerInterface $cookies, bool $removeResponseCookies = true)
    {
        $this->cookies = $cookies;
        $this->removeResponseCookies = $removeResponseCookies;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $this->cookies->send($response, $this->removeResponseCookies);
    }
}
