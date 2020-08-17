<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use DateTimeInterface;

/**
 * Value object representing a cookie.
 *
 * Instances of this interface are considered immutable; all methods that might
 * change state MUST be implemented such that they retain the internal state of
 * the current instance and return an instance that contains the changed state.
 *
 * @link https://tools.ietf.org/html/rfc6265#section-4.1
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie
 */
interface CookieInterface
{
    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the value of the cookie.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Returns an instance with the specified value.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified value.
     *
     * @param string $value
     * @return static
     */
    public function withValue(string $value): CookieInterface;

    /**
     * Gets the max-age attribute.
     *
     * @return int
     */
    public function getMaxAge(): int;

    /**
     * Gets the time the cookie expires.
     *
     * @return int
     */
    public function getExpires(): int;

    /**
     * Whether this cookie is expired.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Returns an instance that will expire immediately.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified expires.
     *
     * @return static
     */
    public function expire(): CookieInterface;

    /**
     * Returns an instance with the specified expires.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified expires.
     *
     * The `$expire` value can be an `DateTimeInterface` instance,
     * a string representation of a date, or a integer Unix timestamp, or `null`.
     *
     * If the `$expire` was not specified or has an empty value,
     * the cookie MUST be converted to a session cookie,
     * which will expire as soon as the browser is closed.
     *
     * @param DateTimeInterface|int|string|null $expire
     * @return static
     */
    public function withExpires($expire = null): CookieInterface;

    /**
     * Gets the domain of the cookie.
     *
     * @return string|null
     */
    public function getDomain(): ?string;

    /**
     * Returns an instance with the specified set of domains.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified set of domains.
     *
     * @param string|null $domain
     * @return static
     */
    public function withDomain(?string $domain): CookieInterface;

    /**
     * Gets the path of the cookie.
     *
     * @return string
     */
    public function getPath(): ?string;

    /**
     * Returns an instance with the specified set of paths.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified set of paths.
     *
     * @param string|null $path
     * @return static
     */
    public function withPath(?string $path): CookieInterface;

    /**
     * Whether the cookie should only be transmitted over a secure HTTPS connection.
     *
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * Returns an instance with the specified enabling or
     * disabling cookie transmission over a secure HTTPS connection.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified security flag.
     *
     * @param bool $secure
     * @return static
     */
    public function withSecure(bool $secure = true): CookieInterface;

    /**
     * Whether the cookie can be accessed only through the HTTP protocol.
     *
     * @return bool
     */
    public function isHttpOnly(): bool;

    /**
     * Returns an instance with the specified enable or
     * disable cookie transmission over the HTTP protocol only.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified httpOnly flag.
     *
     * @param bool $httpOnly
     * @return static
     */
    public function withHttpOnly(bool $httpOnly = true): CookieInterface;

    /**
     * Gets the SameSite attribute.
     *
     * @return string|null
     */
    public function getSameSite(): ?string;

    /**
     * Returns an instance with the specified SameSite attribute.
     *
     * This method MUST retain the state of the current instance,
     * and return a new instance that contains the specified SameSite attribute.
     *
     * @param string|null $sameSite
     * @return static
     */
    public function withSameSite(?string $sameSite): CookieInterface;

    /**
     * Whether this cookie is a session cookie.
     *
     * @return bool
     * @see withExpires()
     */
    public function isSession(): bool;

    /**
     * Returns the cookie as a string representation.
     *
     * @return string
     */
    public function __toString(): string;
}
