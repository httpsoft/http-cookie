<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Cookie;

use ArrayIterator;
use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieInterface;
use HttpSoft\Cookie\CookieManager;
use HttpSoft\Message\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use StdClass;

use function count;

class CookieManagerTest extends TestCase
{
    /**
     * @var array
     */
    private array $cookies;

    /**
     * @var CookieInterface
     */
    private CookieInterface $first;

    /**
     * @var CookieInterface
     */
    private CookieInterface $second;

    public function setUp(): void
    {
        $this->first = new Cookie('first', 'value');
        $this->second = new Cookie('second', '1234');
        $this->cookies = [
            $this->first->getName() => $this->first,
            $this->second->getName() => $this->second,
        ];
    }

    public function testGettersDefault(): void
    {
        $manager = new CookieManager();
        $this->assertNull($manager->get('not exist'));
        $this->assertSame([], $manager->getAll());
        $this->assertNull($manager->getValue('not exist'));
        $this->assertNull($manager->remove('not exist'));
        $this->assertFalse($manager->has('not exist'));
        $this->assertSame(0, $manager->count());
        $this->assertInstanceOf(ArrayIterator::class, $manager->getIterator());
        $this->assertSame(0, $manager->getIterator()->count());
    }

    public function testGettersWithCookiesPassedToConstructor(): void
    {
        $manager = new CookieManager($this->cookies);
        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());
        $this->assertInstanceOf(ArrayIterator::class, $manager->getIterator());
        $this->assertSame(count($this->cookies), $manager->getIterator()->count());

        $this->assertSame($this->first, $manager->get('first'));
        $this->assertSame($this->first->getValue(), $manager->getValue('first'));
        $this->assertTrue($manager->has('first'));
        $this->assertSame($this->first, $manager->remove('first'));

        $this->assertSame($this->second, $manager->get('second'));
        $this->assertSame($this->second->getValue(), $manager->getValue('second'));
        $this->assertTrue($manager->has('second'));
        $this->assertSame($this->second, $manager->remove('second'));
    }

    public function testRemove(): void
    {
        $manager = new CookieManager($this->cookies);
        $this->assertNull($manager->remove('not exist'));

        $this->assertTrue($manager->has('first'));
        $this->assertSame($this->first, $manager->remove('first'));
        $this->assertFalse($manager->has('first'));

        $this->assertTrue($manager->has('second'));
        $this->assertSame($this->second, $manager->remove('second'));
        $this->assertFalse($manager->has('second'));

        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());
    }

    public function testClear(): void
    {
        $manager = new CookieManager();
        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());

        $manager->clear();
        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());

        $manager->setMultiple($this->cookies);
        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());

        $manager->clear();
        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());
    }

    public function testSet(): void
    {
        $manager = new CookieManager();
        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());

        $manager->set($this->first);
        $manager->set($this->second);

        $this->assertSame($this->first, $manager->get('first'));
        $this->assertSame($this->second, $manager->get('second'));
        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());
    }

    public function testSetMultiple(): void
    {
        $manager = new CookieManager();
        $manager->setMultiple($this->cookies);

        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());
        $this->assertInstanceOf(ArrayIterator::class, $manager->getIterator());
        $this->assertSame(count($this->cookies), $manager->getIterator()->count());

        $this->assertSame($this->first, $manager->get('first'));
        $this->assertSame($this->first->getValue(), $manager->getValue('first'));
        $this->assertTrue($manager->has('first'));
        $this->assertSame($this->first, $manager->remove('first'));

        $this->assertSame($this->second, $manager->get('second'));
        $this->assertSame($this->second->getValue(), $manager->getValue('second'));
        $this->assertTrue($manager->has('second'));
        $this->assertSame($this->second, $manager->remove('second'));
    }

    /**
     * @return array
     */
    public function invalidArrayCookiesProvider(): array
    {
        return [
            'array[]' => [ [[]] ],
            'integer[]' => [ [1] ],
            'float[]' => [ [1.1] ],
            'true[]' => [ [true] ],
            'false[]' => [ [false] ],
            'string[]' => [ ['string'] ] ,
            'callable[]' => [ [fn() => null] ],
            'object-not-CookieInterface-instance[]' => [ [new StdClass()] ],
        ];
    }

    /**
     * @dataProvider invalidArrayCookiesProvider
     * @param array $invalidArrayCookies
     */
    public function testConstructorThrowExceptionForPassedInvalidArrayCookies(array $invalidArrayCookies): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CookieManager($invalidArrayCookies);
    }

    /**
     * @dataProvider invalidArrayCookiesProvider
     * @param array $invalidArrayCookies
     */
    public function testSetMultipleThrowExceptionForPassedInvalidArrayCookies(array $invalidArrayCookies): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CookieManager())->setMultiple($invalidArrayCookies);
    }

    public function testSend(): void
    {
        $manager = new CookieManager($this->cookies);
        $emptyResponse = new Response();

        $response = $manager->send($emptyResponse);
        $this->assertNotSame($emptyResponse, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([(string) $this->first, (string) $this->second], $response->getHeader('set-cookie'));
    }

    public function testSendWithEmptyCookies(): void
    {
        $manager = new CookieManager();
        $emptyResponse = new Response();

        $response = $manager->send($emptyResponse);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([], $response->getHeader('set-cookie'));
    }

    public function testSendRepeatedly(): void
    {
        $manager = new CookieManager([$this->first]);
        $emptyResponse = new Response();

        $firstResponse = $manager->send($emptyResponse);
        $this->assertNotSame($emptyResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first], $firstResponse->getHeader('set-cookie'));

        $manager->set($this->second);
        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());
        $this->assertTrue($manager->has('second'));
        $this->assertTrue($manager->has('first'));

        $secondResponse = $manager->send($firstResponse);
        $this->assertNotSame($firstResponse, $secondResponse);
        $this->assertInstanceOf(ResponseInterface::class, $secondResponse);
        $this->assertSame([(string) $this->first, (string) $this->second], $secondResponse->getHeader('set-cookie'));
    }

    public function testSendRepeatedlyAndClear(): void
    {
        $manager = new CookieManager([$this->first]);
        $emptyResponse = new Response();

        $firstResponse = $manager->send($emptyResponse);
        $this->assertNotSame($emptyResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first], $firstResponse->getHeader('set-cookie'));

        $manager->clear();
        $this->assertSame([], $manager->getAll());
        $this->assertSame(0, $manager->count());

        $manager->set($this->second);
        $this->assertSame([$this->second->getName() => $this->second], $manager->getAll());
        $this->assertSame(count([$this->second]), $manager->count());
        $this->assertTrue($manager->has('second'));
        $this->assertFalse($manager->has('first'));

        $secondResponse = $manager->send($firstResponse);
        $this->assertNotSame($firstResponse, $secondResponse);
        $this->assertInstanceOf(ResponseInterface::class, $secondResponse);
        $this->assertSame([(string) $this->second], $secondResponse->getHeader('set-cookie'));
    }

    public function testSendRepeatedlyWithReplace(): void
    {
        $manager = new CookieManager([$this->first]);
        $baseResponse = (new Response())->withHeader('set-cookie', (string) $this->first);

        $firstResponse = $manager->send($baseResponse);
        $this->assertNotSame($baseResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first], $firstResponse->getHeader('set-cookie'));
    }

    public function testSendRepeatedlyWithoutReplace(): void
    {
        $manager = new CookieManager([$this->first]);
        $baseResponse = (new Response())->withHeader('set-cookie', (string) $this->first);

        $firstResponse = $manager->send($baseResponse, false);
        $this->assertNotSame($baseResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first, (string) $this->first], $firstResponse->getHeader('set-cookie'));
    }
}
