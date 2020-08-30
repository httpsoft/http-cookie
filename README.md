# HTTP Cookie

[![License](https://poser.pugx.org/httpsoft/http-cookie/license)](https://packagist.org/packages/httpsoft/http-cookie)
[![Latest Stable Version](https://poser.pugx.org/httpsoft/http-cookie/v)](https://packagist.org/packages/httpsoft/http-cookie)
[![Total Downloads](https://poser.pugx.org/httpsoft/http-cookie/downloads)](https://packagist.org/packages/httpsoft/http-cookie)
[![GitHub Build Status](https://github.com/httpsoft/http-cookie/workflows/build/badge.svg)](https://github.com/httpsoft/http-cookie/actions)
[![GitHub Static Analysis Status](https://github.com/httpsoft/http-cookie/workflows/static/badge.svg)](https://github.com/httpsoft/http-cookie/actions)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/httpsoft/http-cookie/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-cookie/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/httpsoft/http-cookie/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-cookie/?branch=master)

This package provides convenient cookie management in accordance with the [RFC 6265](https://tools.ietf.org/html/rfc6265) specification.

This package supports [PSR-7](https://github.com/php-fig/http-message) and [PSR-15](https://github.com/php-fig/http-factory) interfaces.

## Documentation

* [In English language](https://httpsoft.org/docs/cookie).
* [In Russian language](https://httpsoft.org/ru/docs/cookie).

## Installation

This package requires PHP version 7.4 or later.

```
composer require httpsoft/http-cookie
```

## Usage

```php
use HttpSoft\Cookie\Cookie;
use HttpSoft\Cookie\CookieCreator;
use HttpSoft\Cookie\CookieManager;
use HttpSoft\Cookie\CookieSendMiddleware;

/**
 * @var Psr\Http\Message\ResponseInterface $response
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 */
 
$manager = new CookieManager();

// Create cookie
$cookie1 = new Cookie('test', 'value', '+1 hour');
// or
$cookie2 = CookieCreator::create('test2', 'value', time() + 3600, '.example.com', '/path');
// or from raw `Set-Cookie` header
$cookie3 = CookieCreator::createFromString('name=value; Path=/; Secure; HttpOnly; SameSite=Lax; ...');

// Set cookies to the manager
$manager->set($cookie1);
$manager->set($cookie2);
$manager->set($cookie3);

// Set all cookie to the response for sending
$response = $manager->send($response);
// or use `CookieSendMiddleware` middleware
$middleware = new CookieSendMiddleware($manager);
$response = $middleware->process($request, $handler);

// Emit a response to the client
// ...
```
