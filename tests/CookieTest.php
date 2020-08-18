<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Cookie;

use DateTime;
use DateTimeImmutable;
use HttpSoft\Cookie\Cookie;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function strtotime;
use function time;

class CookieTest extends TestCase
{
    /**
     * @var Cookie
     */
    private Cookie $cookie;

    public function setUp(): void
    {
        $this->cookie = new Cookie('name');
    }

    public function testGettersDefault(): void
    {
        $cookie = new Cookie($name = 'name');
        $this->assertSame($name, $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertSame(0, $cookie->getMaxAge());
        $this->assertSame(0, $cookie->getExpires());
        $this->assertNull($cookie->getDomain());
        $this->assertSame('/', $cookie->getPath());
        $this->assertFalse($cookie->isExpired());
        $this->assertTrue($cookie->isSession());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAME_SITE_LAX, $cookie->getSameSite());
        $this->assertSame('name=; Path=/; Secure; HttpOnly; SameSite=Lax', (string) $cookie);
        $this->assertSame('name=; Path=/; Secure; HttpOnly; SameSite=Lax', $cookie->__toString());
    }

    public function testGettersWithParametersPassedToConstructor(): void
    {
        $cookie = new Cookie(
            $name = 'name',
            $value = 'value',
            $expire = time() + ($maxAge = 3600),
            $domain = '.example.com',
            $path = '/path',
            $secure = null,
            $httpOnly = false,
            $sameSite = Cookie::SAME_SITE_STRICT
        );
        $this->assertSame($name, $cookie->getName());
        $this->assertSame($value, $cookie->getValue());
        $this->assertSame($maxAge, $cookie->getMaxAge());
        $this->assertSame($expire, $cookie->getExpires());
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertSame($path, $cookie->getPath());
        $this->assertFalse($cookie->isExpired());
        $this->assertFalse($cookie->isSession());
        $this->assertFalse($cookie->isSecure());
        $this->assertSame($httpOnly, $cookie->isHttpOnly());
        $this->assertSame($sameSite, $cookie->getSameSite());

        $expected = "{$name}={$value}; Expires=" . gmdate('D, d-M-Y H:i:s T', $expire)
            . "; Max-Age={$cookie->getMaxAge()}; Domain={$domain}; Path={$path}; SameSite={$sameSite}";
        $this->assertSame($expected, (string) $cookie);
        $this->assertSame($expected, $cookie->__toString());
    }

    public function testConstructorThrowExceptionForPassedEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cookie('');
    }

    /**
     * @return array
     */
    public function invalidNameProvider(): array
    {
        return [
            ['"'], ['@'], ['('], [')'], ['='], ['\\'], ['['], [']'], [','], [';'], [':'],
            ['<'], ['>'], ['?'], ['/'], ['{'], ['}'], ['name[]'], ["\x00"], ["\x1F"],
        ];
    }

    /**
     * @dataProvider invalidNameProvider
     * @param string $name
     */
    public function testConstructorThrowExceptionForPassedInvalidName($name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cookie($name);
    }

    public function testWithValue(): void
    {
        $cookie = $this->cookie->withValue('value');
        $this->assertNotSame($this->cookie, $cookie);
        $this->assertNotSame($this->cookie->getValue(), $cookie->getValue());
        $this->assertSame('name=value; Path=/; Secure; HttpOnly; SameSite=Lax', $cookie->__toString());

        $new = $cookie->withValue('<value>');
        $this->assertNotSame($cookie, $new);
        $this->assertNotSame($cookie->getValue(), $new->getValue());
        $this->assertSame('name=%3Cvalue%3E; Path=/; Secure; HttpOnly; SameSite=Lax', (string) $new);
    }

    public function testWithValueIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withValue('value');
        $this->assertSame('name=value; Path=/; Secure; HttpOnly; SameSite=Lax', $cookie->__toString());

        $new = $cookie->withValue('value');
        $this->assertSame($cookie, $new);
        $this->assertSame($cookie->getValue(), $new->getValue());
        $this->assertSame('name=value; Path=/; Secure; HttpOnly; SameSite=Lax', (string) $new);
    }

    public function testExpire(): void
    {
        $this->assertFalse($this->cookie->isExpired());
        $this->assertTrue($this->cookie->isSession());

        $cookie = $this->cookie->expire();
        $this->assertTrue($cookie->isExpired());
        $this->assertFalse($cookie->isSession());
        $this->assertNotSame($this->cookie, $cookie);
        $this->assertNotSame((string) $this->cookie, (string) $cookie);
    }

    public function testExpireIfAlreadyExpiredDoNotClone(): void
    {
        $cookie = $this->cookie->expire();
        $this->assertNotSame($this->cookie, $cookie);
        $this->assertTrue($cookie->isExpired());
        $this->assertFalse($cookie->isSession());

        $new = $cookie->expire();
        $this->assertSame($cookie, $new);
        $this->assertTrue($new->isExpired());
        $this->assertFalse($new->isSession());
        $this->assertSame((string) $cookie, (string) $new);
    }

    public function testWithExpiresTime(): void
    {
        $cookie = $this->cookie->withExpires($expire = time() + 3600);
        $this->assertSame($expire, $cookie->getExpires());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withExpires($newExpire = time() + 86400);
        $this->assertSame($newExpire, $new->getExpires());
        $this->assertNotSame($cookie, $new);
    }

    public function testWithExpiresDateTimeInterface(): void
    {
        $cookie = $this->cookie->withExpires($expire = new DateTimeImmutable());
        $this->assertSame($expire->getTimestamp(), $cookie->getExpires());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withExpires($newExpire = new DateTimeImmutable('+1 day'));
        $this->assertSame($newExpire->getTimestamp(), $new->getExpires());
        $this->assertNotSame($cookie, $new);

        $newWithDateTime = $cookie->withExpires($expireDateTime = new DateTime());
        $this->assertSame($expireDateTime->getTimestamp(), $newWithDateTime->getExpires());
        $this->assertNotSame($cookie, $newWithDateTime);
        $this->assertNotSame($new, $newWithDateTime);
    }

    public function testWithExpiresString(): void
    {
        $expireString = '+1 day';
        $expire = strtotime($expireString);

        $cookie = $this->cookie->withExpires($expireString);
        $this->assertSame($expire, $cookie->getExpires());
        $this->assertNotSame($this->cookie, $cookie);

        $newExpire = time() + 3600;
        $new = $cookie->withExpires((string) $newExpire);

        $this->assertSame($newExpire, $new->getExpires());
        $this->assertNotSame($cookie, $new);
    }

    public function testWithExpiresEmpty(): void
    {
        $cookie = (new Cookie('name', 'value', $expire = 0));
        $this->assertSame($expire, $cookie->getExpires());

        $new = $cookie->withExpires($expire);
        $this->assertSame($expire, $new->getExpires());

        $newWithNull = $cookie->withExpires(null);
        $this->assertSame($expire, $newWithNull->getExpires());

        $newWithEmptyString = $cookie->withExpires('');
        $this->assertSame($expire, $newWithEmptyString->getExpires());
    }

    public function testWithExpiresIfNotChangedDoNotClone(): void
    {
        $cookie = (new Cookie('name', 'value', $expire = 0));
        $this->assertSame($expire, $cookie->getExpires());

        $new = $cookie->withExpires($expire);
        $this->assertSame($expire, $new->getExpires());
        $this->assertSame($cookie, $new);
    }

    /**
     * @return array
     */
    public function invalidExpireProvider(): array
    {
        return [
            'array' => [[]],
            'float' => [1.1],
            'true' => [true],
            'false' => [false],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
            'unsupported-string-value' => ['unsupported-string-value'],
        ];
    }

    /**
     * @dataProvider invalidExpireProvider
     * @param mixed $expire
     */
    public function testConstructorThrowExceptionForPassedInvalidExpire($expire): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cookie('name', 'value', $expire);
    }

    /**
     * @dataProvider invalidExpireProvider
     * @param mixed $expire
     */
    public function testWithExpiresThrowExceptionForPassedInvalidExpire($expire): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cookie->withExpires($expire);
    }

    public function testWithDomain(): void
    {
        $cookie = $this->cookie->withDomain($domain = 'example.com');
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withDomain($newDomain = '.example.com');
        $this->assertSame($newDomain, $new->getDomain());
        $this->assertNotSame($cookie, $new);
    }

    public function testWithDomainEmpty(): void
    {
        $cookie = $this->cookie->withDomain($domain = null);
        $this->assertSame($domain, $cookie->getDomain());

        $new = $cookie->withDomain('');
        $this->assertSame($domain, $new->getDomain());
    }

    public function testWithDomainIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withDomain($domain = '.example.com');
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withDomain($cookie->getDomain());
        $this->assertSame($domain, $new->getDomain());
        $this->assertSame($cookie, $new);
    }

    public function testWithPath(): void
    {
        $cookie = $this->cookie->withPath($path = '/path');
        $this->assertSame($path, $cookie->getPath());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withPath($newDomain = '/path/to/target');
        $this->assertSame($newDomain, $new->getPath());
        $this->assertNotSame($cookie, $new);
    }

    public function testWithPathEmpty(): void
    {
        $cookie = $this->cookie->withPath($path = null);
        $this->assertSame($path, $cookie->getPath());

        $new = $cookie->withPath('');
        $this->assertSame($path, $new->getPath());
    }

    public function testWithPathIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withPath($path = '/path/to/target');
        $this->assertSame($path, $cookie->getPath());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withPath($cookie->getPath());
        $this->assertSame($path, $new->getPath());
        $this->assertSame($cookie, $new);
    }

    public function testWithSecure(): void
    {
        $cookie = $this->cookie->withSecure(false);
        $this->assertFalse($cookie->isSecure());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withSecure(true);
        $this->assertTrue($new->isSecure());
        $this->assertNotSame($cookie, $new);
        $this->assertNotSame($this->cookie, $new);
    }

    public function testWithSecureIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withSecure();
        $this->assertTrue($cookie->isSecure());
        $this->assertSame($this->cookie, $cookie);

        $new = $cookie->withSecure($cookie->isSecure());
        $this->assertTrue($new->isSecure());
        $this->assertSame($cookie, $new);
        $this->assertSame($this->cookie, $new);
    }

    public function testWithHttpOnly(): void
    {
        $cookie = $this->cookie->withHttpOnly(false);
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withHttpOnly(true);
        $this->assertTrue($new->isHttpOnly());
        $this->assertNotSame($cookie, $new);
        $this->assertNotSame($this->cookie, $new);
    }

    public function testWithHttpOnlyIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withHttpOnly();
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame($this->cookie, $cookie);

        $new = $cookie->withHttpOnly($cookie->isHttpOnly());
        $this->assertTrue($new->isHttpOnly());
        $this->assertSame($cookie, $new);
        $this->assertSame($this->cookie, $new);
    }

    public function testWithSameSite(): void
    {
        $cookie = $this->cookie->withSameSite(Cookie::SAME_SITE_STRICT);
        $this->assertSame(Cookie::SAME_SITE_STRICT, $cookie->getSameSite());
        $this->assertNotSame($this->cookie, $cookie);

        $new = $cookie->withSameSite(Cookie::SAME_SITE_NONE);
        $this->assertSame(Cookie::SAME_SITE_NONE, $new->getSameSite());
        $this->assertNotSame($cookie, $new);
    }

    public function testWithSameSiteEmpty(): void
    {
        $cookie = $this->cookie->withSameSite($sameSite = null);
        $this->assertSame($sameSite, $cookie->getSameSite());

        $new = $cookie->withSameSite('');
        $this->assertSame($sameSite, $new->getSameSite());
    }

    public function testWithSameSiteIfNotChangedDoNotClone(): void
    {
        $cookie = $this->cookie->withSameSite(Cookie::SAME_SITE_LAX);
        $this->assertSame(Cookie::SAME_SITE_LAX, $cookie->getSameSite());
        $this->assertSame($this->cookie, $cookie);

        $new = $cookie->withSameSite($cookie->getSameSite());
        $this->assertSame(Cookie::SAME_SITE_LAX, $new->getSameSite());
        $this->assertSame($cookie, $new);
        $this->assertSame($this->cookie, $new);
    }

    public function testConstructorThrowExceptionForPassedInvalidSameSite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cookie('name', 'value', null, null, '/', true, true, 'unsupported-string-value');
    }

    public function testWithSameSiteThrowExceptionForPassedInvalidSameSite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cookie->withSameSite('unsupported-string-value');
    }
}
