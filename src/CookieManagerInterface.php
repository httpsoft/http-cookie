<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use Countable;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;

/**
 * @psalm-suppress MissingTemplateParam
 */
interface CookieManagerInterface extends Countable, IteratorAggregate
{
    /**
     * Sets a cookie.
     *
     * @param CookieInterface $cookie the cookie to set.
     */
    public function set(CookieInterface $cookie): void;

    /**
     * Sets multiple cookies.
     *
     * @param CookieInterface[] $cookies multiple cookies to set.
     */
    public function setMultiple(array $cookies): void;

    /**
     * Gets the cookie with the specified name.
     *
     * @param string $name the cookie name.
     * @return CookieInterface|null the cookie or `null` if the cookie does not exist.
     */
    public function get(string $name): ?CookieInterface;

    /**
     * Gets all cookies.
     *
     * @return CookieInterface[] all cookies, or an empty array if no cookies exist.
     */
    public function getAll(): array;

    /**
     * Gets the value of the named cookie.
     *
     * @param string $name the cookie name.
     * @return string|null the value of the named cookie or `null` if the cookie does not exist.
     */
    public function getValue(string $name): ?string;

    /**
     * Whether a cookie with the specified name exists.
     *
     * @param string $name the cookie name.
     * @return bool whether the named cookie exists.
     */
    public function has(string $name): bool;

    /**
     * Removes a cookie.
     *
     * @param string $name the name of the cookie to be removed.
     * @return CookieInterface|null cookie that is removed.
     */
    public function remove(string $name): ?CookieInterface;

    /**
     * Removes all cookies.
     */
    public function clear(): void;

    /**
     * Sets all cookie to the response and returns a clone instance of the response with the cookies set.
     *
     * This method must be called before emitting the response.
     *
     * @link https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php
     *
     * @param ResponseInterface $response
     * @param bool $removeResponseCookies whether to remove previously set cookies from the response.
     * @return ResponseInterface response with cookies set.
     */
    public function send(ResponseInterface $response, bool $removeResponseCookies = true): ResponseInterface;
}
