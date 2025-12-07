<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;
/**
 * Container for cookies to be send out to the user.
 *
 * Cookies are used to store user-related data within the user-agent
 * e.g. to help detecting that requests are done by the same user.
 * Common applications are session cookies or low-level signon help.
 *
 * @link  http://wp.netscape.com/newsref/std/cookie_spec.html
 * @link  http://www.faqs.org/rfcs/rfc2109.html
 */
class Cookie
{
    /** timestamp when cookie expires */
    private int $expires = 0;
    /** path for which the cookie should be available */
    private ?string $path = null;
    /** domain where this cookie will be available */
    private ?string $domain = null;
    /**switch whether cookie should only be used in secure connections */
    private bool $secure = false;
    /** switch whether cookie should only be accessible through http */
    private bool $httpOnly = true;

    public function __construct(private string $name, private ?string $value = null) { }

    /**
     * creates the cookie
     */
    public static function create(string $name, ?string $value = null): self
    {
        return new self($name, $value);
    }

    /**
     * set the timestamp when the cookie will expire
     *
     * Please note that $expires must be a timestamp in the future.
     *
     * @param  int  $expires  timestamp in seconds since 1970
     */
    public function expiringAt(int $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * sets the cookie to expire after given amount of seconds
     *
     * The method will add the current timestamp to the given amount of seconds.
     *
     * @since  1.5.0
     */
    public function expiringIn(int $seconds): self
    {
        $this->expires = time() + $seconds;
        return $this;
    }

    /**
     * set the path for which the cookie should be available
     */
    public function forPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * set the domain where this cookie will be available
     */
    public function forDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * set cookie only on secure connections
     */
    public function restrictToSsl(): self
    {
        $this->secure = true;
        return $this;
    }

    /**
     * disable setting the cookie as http only
     */
    public function disableHttpOnly(): self
    {
        $this->httpOnly = false;
        return $this;
    }

    /**
     * returns name of cookie
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * returns value of cookie
     */
    public function value(): ?string
    {
        return $this->value;
    }

    /**
     * returns expiration timestamp of cookie
     */
    public function expiration(): int
    {
        return $this->expires;
    }

    /**
     * returns path of cookie
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * returns domain of cookie
     */
    public function domain(): ?string
    {
        return $this->domain;
    }

    /**
     * checks whether cookie should only be sst on secure connections
     */
    public function isRestrictedToSsl(): bool
    {
        return $this->secure;
    }

    /**
     * checks whether cookie should only be accessible through http
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function send(): void
    {
        setcookie(
            $this->name,
            (string) $this->value, // force empty string in case value is null
            $this->expires,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly
        );
    }
}
