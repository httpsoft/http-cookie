<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use DateTimeInterface;
use InvalidArgumentException;

use function array_shift;
use function explode;
use function in_array;
use function preg_split;
use function sprintf;
use function strtolower;
use function time;
use function urldecode;

use const PREG_SPLIT_NO_EMPTY;

final class CookieCreator
{
    /**
     * Creates an instance of the `HttpSoft\Cookie\Cookie` from attributes.
     *
     * @param string $name the name of the cookie.
     * @param string $value the value of the cookie.
     * @param DateTimeInterface|int|string|null $expire the time the cookie expire.
     * @param string|null $path the set of paths for the cookie.
     * @param string|null $domain the set of domains for the cookie.
     * @param bool|null $secure whether the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool|null $httpOnly whether the cookie can be accessed only through the HTTP protocol.
     * @param string|null $sameSite whether the cookie will be available for cross-site requests.
     * @return CookieInterface
     * @throws InvalidArgumentException if one or more arguments are not valid.
     */
    public static function create(
        string $name,
        string $value = '',
        $expire = null,
        ?string $domain = null,
        ?string $path = '/',
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = Cookie::SAME_SITE_LAX
    ): CookieInterface {
        return new Cookie($name, $value, $expire, $domain, $path, $secure, $httpOnly, $sameSite);
    }

    /**
     * Creates an instance of the `HttpSoft\Cookie\Cookie` from raw `Set-Cookie` header.
     *
     * @param string $string raw `Set-Cookie` header value.
     * @return CookieInterface
     * @throws InvalidArgumentException if the raw `Set-Cookie` header value is not valid.
     */
    public static function createFromString(string $string): CookieInterface
    {
        if (!$attributes = preg_split('/\s*;\s*/', $string, -1, PREG_SPLIT_NO_EMPTY)) {
            throw new InvalidArgumentException(sprintf(
                'The raw value of the `Set Cookie` header `%s` could not be parsed.',
                $string
            ));
        }

        $nameAndValue = explode('=', array_shift($attributes), 2);
        $cookie = ['name' => $nameAndValue[0], 'value' => isset($nameAndValue[1]) ? urldecode($nameAndValue[1]) : ''];

        while ($attribute = array_shift($attributes)) {
            $attribute = explode('=', $attribute, 2);
            $attributeName = strtolower($attribute[0]);
            $attributeValue = $attribute[1] ?? null;

            if (in_array($attributeName, ['expires', 'domain', 'path', 'samesite'], true)) {
                $cookie[$attributeName] = $attributeValue;
                continue;
            }

            if (in_array($attributeName, ['secure', 'httponly'], true)) {
                $cookie[$attributeName] = true;
                continue;
            }

            if ($attributeName === 'max-age') {
                $cookie['expires'] = time() + (int) $attributeValue;
            }
        }

        return new Cookie(
            $cookie['name'],
            $cookie['value'],
            $cookie['expires'] ?? null,
            $cookie['domain'] ?? null,
            $cookie['path'] ?? null,
            $cookie['secure'] ?? null,
            $cookie['httponly'] ?? null,
            $cookie['samesite'] ?? null
        );
    }
}
