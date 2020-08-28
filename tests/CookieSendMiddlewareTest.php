<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Cookie;

use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieInterface;
use HttpSoft\Cookie\CookieManager;
use HttpSoft\Cookie\CookieSendMiddleware;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Tests\Cookie\TestAsset\WrapResponseRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function count;

class CookieSendMiddlewareTest extends TestCase
{
    /**
     * @var array
     */
    private array $cookies;

    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

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
        $this->request = new ServerRequest();
    }

    public function testProcess(): void
    {
        $emptyResponse = new Response();
        $manager = new CookieManager($this->cookies);
        $handler = new WrapResponseRequestHandler($emptyResponse);

        $middleware = new CookieSendMiddleware($manager);
        $response = $middleware->process($this->request, $handler);

        $this->assertNotSame($emptyResponse, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([(string) $this->first, (string) $this->second], $response->getHeader('set-cookie'));
    }

    public function testProcessWithEmptyCookies(): void
    {
        $emptyResponse = new Response();
        $manager = new CookieManager();
        $handler = new WrapResponseRequestHandler($emptyResponse);

        $middleware = new CookieSendMiddleware($manager);
        $response = $middleware->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([], $response->getHeader('set-cookie'));
    }

    public function testProcessRepeatedly(): void
    {
        $emptyResponse = new Response();
        $manager = new CookieManager([$this->first]);

        $middleware = new CookieSendMiddleware($manager);
        $handler = new WrapResponseRequestHandler($emptyResponse);

        $firstResponse = $middleware->process($this->request, $handler);
        $this->assertNotSame($emptyResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first], $firstResponse->getHeader('set-cookie'));

        $manager->set($this->second);
        $this->assertSame($this->cookies, $manager->getAll());
        $this->assertSame(count($this->cookies), $manager->count());
        $this->assertTrue($manager->has('second'));
        $this->assertTrue($manager->has('first'));

        $middleware = new CookieSendMiddleware($manager);
        $handler = new WrapResponseRequestHandler($firstResponse);

        $secondResponse = $middleware->process($this->request, $handler);
        $this->assertNotSame($firstResponse, $secondResponse);
        $this->assertInstanceOf(ResponseInterface::class, $secondResponse);
        $this->assertSame([(string) $this->first, (string) $this->second], $secondResponse->getHeader('set-cookie'));
    }

    public function testProcessRepeatedlyAndClear(): void
    {
        $emptyResponse = new Response();
        $manager = new CookieManager([$this->first]);

        $middleware = new CookieSendMiddleware($manager);
        $handler = new WrapResponseRequestHandler($emptyResponse);

        $firstResponse = $middleware->process($this->request, $handler);
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

        $middleware = new CookieSendMiddleware($manager);
        $handler = new WrapResponseRequestHandler($firstResponse);

        $secondResponse = $middleware->process($this->request, $handler);
        $this->assertNotSame($firstResponse, $secondResponse);
        $this->assertInstanceOf(ResponseInterface::class, $secondResponse);
        $this->assertSame([(string) $this->second], $secondResponse->getHeader('set-cookie'));
    }

    public function testProcessRepeatedlyWithReplace(): void
    {
        $baseResponse = (new Response())->withHeader('set-cookie', (string) $this->first);
        $manager = new CookieManager([$this->first]);

        $middleware = new CookieSendMiddleware($manager);
        $handler = new WrapResponseRequestHandler($baseResponse);

        $firstResponse = $middleware->process($this->request, $handler);
        $this->assertNotSame($baseResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first], $firstResponse->getHeader('set-cookie'));
    }

    public function testProcessRepeatedlyWithoutReplace(): void
    {
        $baseResponse = (new Response())->withHeader('set-cookie', (string) $this->first);
        $manager = new CookieManager([$this->first]);

        $middleware = new CookieSendMiddleware($manager, false);
        $handler = new WrapResponseRequestHandler($baseResponse);

        $firstResponse = $middleware->process($this->request, $handler);
        $this->assertNotSame($baseResponse, $firstResponse);
        $this->assertInstanceOf(ResponseInterface::class, $firstResponse);
        $this->assertSame([(string) $this->first, (string) $this->first], $firstResponse->getHeader('set-cookie'));
    }
}
