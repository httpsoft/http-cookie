<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use ArrayIterator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

use function count;
use function gettype;
use function get_class;
use function is_object;

final class CookieManager implements CookieManagerInterface
{
    /**
     * @var CookieInterface[]
     */
    private array $cookies = [];

    /**
     * @param CookieInterface[] $cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->setMultiple($cookies);
    }

    /**
     * {@inheritDoc}
     */
    public function set(CookieInterface $cookie): void
    {
        $this->cookies[$cookie->getName()] = $cookie;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function setMultiple(array $cookies): void
    {
        foreach ($cookies as $cookie) {
            if (!($cookie instanceof CookieInterface)) {
                throw new InvalidArgumentException(sprintf(
                    'The `$cookies` array can only contain instances of'
                    . ' `HttpSoft\Cookie\CookieInterface`; received `%s`.',
                    (is_object($cookie) ? get_class($cookie) : gettype($cookie))
                ));
            }

            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): ?CookieInterface
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return $this->cookies;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(string $name): ?string
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name]->getValue() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name): ?CookieInterface
    {
        if (!isset($this->cookies[$name])) {
            return null;
        }

        $removed = $this->cookies[$name];
        unset($this->cookies[$name]);

        return $removed;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * {@inheritDoc}
     */
    public function send(ResponseInterface $response, bool $removeResponseCookies = true): ResponseInterface
    {
        if ($removeResponseCookies) {
            $response = $response->withoutHeader('set-cookie');
        }

        foreach ($this->cookies as $cookie) {
            $response = $response->withAddedHeader('set-cookie', (string) $cookie);
        }

        return $response;
    }
}
