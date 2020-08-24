<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Cookie;

use DateTimeImmutable;
use DateTimeInterface;
use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieCreator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CookieCreatorTest extends TestCase
{
    public function testCreate(): void
    {
        $cookieExpected = new Cookie(
            $name = 'name',
            $value = 'value',
            $expire = time() + ($maxAge = 3600),
            $domain = '.example.com',
            $path = '/path',
            $secure = true,
            $httpOnly = false,
            $sameSite = Cookie::SAME_SITE_STRICT
        );
        $cookie = CookieCreator::create($name, $value, $expire, $domain, $path, $secure, $httpOnly, $sameSite);

        $this->assertSame($name, $cookie->getName());
        $this->assertSame($value, $cookie->getValue());
        $this->assertSame($maxAge, $cookie->getMaxAge());
        $this->assertSame($expire, $cookie->getExpires());
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertSame($path, $cookie->getPath());
        $this->assertFalse($cookie->isExpired());
        $this->assertFalse($cookie->isSession());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame($sameSite, $cookie->getSameSite());
        $this->assertSame((string) $cookieExpected, (string) $cookie);
    }

    public function testCreateThrowExceptionForInvalidAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CookieCreator::create('');

        $this->expectException(InvalidArgumentException::class);
        CookieCreator::create('name', 'value', 'unsupported-expire');

        $this->expectException(InvalidArgumentException::class);
        CookieCreator::create('name', '', null, null, null, null, null, 'unsupported-sameSite');
    }

    public function testCreateWithDifferentExpirationTypes(): void
    {
        $expireString = '+1 day';
        $expireTime = strtotime($expireString);
        $expireDate = new DateTimeImmutable($expireString);

        $cookieFromExpireString = CookieCreator::create('name', 'value', $expireString);
        $cookieFromExpireTime = CookieCreator::create('name', 'value', $expireTime);
        $cookieFromExpireDate = CookieCreator::create('name', 'value', $expireDate);

        $this->assertSame((string) $cookieFromExpireString, (string) $cookieFromExpireTime);
        $this->assertSame((string) $cookieFromExpireString, (string) $cookieFromExpireDate);
        $this->assertSame((string) $cookieFromExpireDate, (string) $cookieFromExpireTime);
    }

    public function testCreateFromString(): void
    {
        $expireDate = new DateTimeImmutable('+60 minutes');
        $cookieString = 'test=56kda89htq; Domain=example.com; Path=/path/to/target;';
        $cookieString .= ' Expires=' . $expireDate->format(DateTimeInterface::RFC7231) . ';';
        $cookieString .= ' Max-Age=3600; Secure; SameSite=Strict; ExtraKey; OtherKey=OtherValue';

        $cookie = new Cookie(
            'test',
            '56kda89htq',
            $expireDate,
            'example.com',
            '/path/to/target',
            true,
            false,
            Cookie::SAME_SITE_STRICT
        );

        $cookieFromString = CookieCreator::createFromString($cookieString);
        $this->assertSame((string) $cookie, (string) $cookieFromString);
    }

    public function testCreateFromStringThrowExceptionForInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CookieCreator::createFromString('');

        $this->expectException(InvalidArgumentException::class);
        CookieCreator::createFromString('name[]');
    }
}
