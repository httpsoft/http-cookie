<?php

declare(strict_types=1);

namespace HttpSoft\Cookie;

use DateTimeInterface;
use InvalidArgumentException;

use function array_map;
use function get_class;
use function gettype;
use function gmdate;
use function implode;
use function in_array;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match;
use function rawurlencode;
use function sprintf;
use function strtolower;
use function strtotime;
use function time;
use function ucfirst;

final class Cookie implements CookieInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $value;

    /**
     * @var int
     */
    private int $expires;

    /**
     * @var string|null
     */
    private ?string $domain;

    /**
     * @var string|null
     */
    private ?string $path;

    /**
     * @var bool|null
     */
    private ?bool $secure;

    /**
     * @var bool|null
     */
    private ?bool $httpOnly;

    /**
     * @var string|null
     */
    private ?string $sameSite;

    /**
     * @param string $name the name of the cookie.
     * @param string $value the value of the cookie.
     * @param DateTimeInterface|int|string|null $expire the time the cookie expire.
     * @param string|null $path the set of paths for the cookie.
     * @param string|null $domain the set of domains for the cookie.
     * @param bool|null $secure whether the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool|null $httpOnly whether the cookie can be accessed only through the HTTP protocol.
     * @param string|null $sameSite whether the cookie will be available for cross-site requests.
     * @throws InvalidArgumentException if one or more arguments are not valid.
     */
    public function __construct(
        string $name,
        string $value = '',
        $expire = null,
        ?string $domain = null,
        ?string $path = '/',
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = self::SAME_SITE_LAX
    ) {
        $this->setName($name);
        $this->setValue($value);
        $this->setExpires($expire);
        $this->setDomain($domain);
        $this->setPath($path);
        $this->setSecure($secure);
        $this->setHttpOnly($httpOnly);
        $this->setSameSite($sameSite);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function withValue(string $value): CookieInterface
    {
        if ($value === $this->value) {
            return $this;
        }

        $new = clone $this;
        $new->setValue($value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAge(): int
    {
        $maxAge = $this->expires - time();
        return $maxAge > 0 ? $maxAge : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * {@inheritDoc}
     */
    public function isExpired(): bool
    {
        return (!$this->isSession() && $this->expires < time());
    }

    /**
     * {@inheritDoc}
     */
    public function expire(): CookieInterface
    {
        if ($this->isExpired()) {
            return $this;
        }

        $new = clone $this;
        $new->expires = time() - 31536001;
        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException if the expire time is not valid.
     */
    public function withExpires($expire = null): CookieInterface
    {
        if ($expire === $this->expires) {
            return $this;
        }

        $new = clone $this;
        $new->setExpires($expire);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * {@inheritDoc}
     */
    public function withDomain(?string $domain): CookieInterface
    {
        if ($domain === $this->domain) {
            return $this;
        }

        $new = clone $this;
        $new->setDomain($domain);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath(?string $path): CookieInterface
    {
        if ($path === $this->path) {
            return $this;
        }

        $new = clone $this;
        $new->setPath($path);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function isSecure(): bool
    {
        return $this->secure ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function withSecure(bool $secure = true): CookieInterface
    {
        if ($secure === $this->secure) {
            return $this;
        }

        $new = clone $this;
        $new->setSecure($secure);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function withHttpOnly(bool $httpOnly = true): CookieInterface
    {
        if ($httpOnly === $this->httpOnly) {
            return $this;
        }

        $new = clone $this;
        $new->setHttpOnly($httpOnly);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException if the sameSite is not valid.
     */
    public function withSameSite(?string $sameSite): CookieInterface
    {
        if ($sameSite === $this->sameSite) {
            return $this;
        }

        $new = clone $this;
        $new->setSameSite($sameSite);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function isSession(): bool
    {
        return $this->expires === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        $cookie = $this->name . '=' . rawurlencode($this->value);

        if (!$this->isSession()) {
            $cookie .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $this->expires);
            $cookie .= '; Max-Age=' . $this->getMaxAge();
        }

        if ($this->domain !== null) {
            $cookie .= '; Domain=' . $this->domain;
        }

        if ($this->path !== null) {
            $cookie .= '; Path=' . $this->path;
        }

        if ($this->secure === true) {
            $cookie .= '; Secure';
        }

        if ($this->httpOnly === true) {
            $cookie .= '; HttpOnly';
        }

        if ($this->sameSite !== null) {
            $cookie .= '; SameSite=' . $this->sameSite;
        }

        return $cookie;
    }

    /**
     * @param string $name
     * @throws InvalidArgumentException if the name is not valid.
     */
    private function setName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }

        if (!preg_match('/^[a-zA-Z0-9!#$%&\' *+\-.^_`|~]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                'The cookie name `%s` contains invalid characters; must contain any US-ASCII'
                . ' characters, except control and separator characters, spaces, or tabs.',
                $name
            ));
        }

        $this->name = $name;
    }

    /**
     * @param string $value
     */
    private function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @param mixed $expire
     * @throws InvalidArgumentException if the expire time is not valid.
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    private function setExpires($expire): void
    {
        if ($expire !== null && !is_int($expire) && !is_string($expire) && !$expire instanceof DateTimeInterface) {
            throw new InvalidArgumentException(sprintf(
                'The cookie expire time is not valid; must be null, or string,'
                . ' or integer, or DateTimeInterface instance; received `%s`.',
                (is_object($expire) ? get_class($expire) : gettype($expire))
            ));
        }

        if (empty($expire)) {
            $this->expires = 0;
            return;
        }

        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $stringExpire = $expire;
            $expire = strtotime($expire);

            if ($expire === false) {
                throw new InvalidArgumentException(sprintf(
                    'The string representation of the cookie expire time `%s` is not valid.',
                    $stringExpire
                ));
            }
        }

        $this->expires = ($expire > 0) ? (int) $expire : 0;
    }

    /**
     * @param string|null $domain
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    private function setDomain(?string $domain): void
    {
        $this->domain = empty($domain) ? null : $domain;
    }

    /**
     * @param string|null $path
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    private function setPath(?string $path): void
    {
        $this->path = empty($path) ? null : $path;
    }

    /**
     * @param bool|null $secure
     */
    private function setSecure(?bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * @param bool|null $httpOnly
     */
    private function setHttpOnly(?bool $httpOnly): void
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * @param string|null $sameSite
     * @throws InvalidArgumentException if the sameSite is not valid.
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    private function setSameSite(?string $sameSite): void
    {
        $sameSite = empty($sameSite) ? null : ucfirst(strtolower($sameSite));
        $sameSiteValues = [self::SAME_SITE_NONE, self::SAME_SITE_LAX, self::SAME_SITE_STRICT];

        if ($sameSite !== null && !in_array($sameSite, $sameSiteValues, true)) {
            throw new InvalidArgumentException(sprintf(
                'The sameSite attribute `%s` is not valid; must be one of (%s).',
                $sameSite,
                implode(', ', array_map(static fn($item) => "\"$item\"", $sameSiteValues)),
            ));
        }

        $this->sameSite = $sameSite;
    }
}
