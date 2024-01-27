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
 * Represents value of a cache control response header.
 *
 * After construction, only the private directive is enabled. All other
 * directives must be enabled explicitly, while the private must be disabled
 * when it is not wished. Only exception to this is the public directive -
 * enabling this automatically disables the private directive.
 *
 * @since  5.1.0
 * @see    http://tools.ietf.org/html/rfc7234#section-5.2.2
 */
class CacheControl
{
    public const HEADER_NAME = 'Cache-Control';
    /** whether the must-revalidate directive is enabled */
    private bool $mustRevalidate  = false;
    /** whether the no-cache directive is enabled */
    private bool $noCache = false;
    /** whether the no-store directive is enabled */
    private bool $noStore = false;
    /** whether the no-transform directive is enabled */
    private bool $noTransform = false;
    /** whether the public directive is enabled */
    private bool $public = false;
    /** whether the private directive is enabled */
    private bool $private = true;
    /** whether the proxy-revalidate directive is enabled */
    private bool $proxyRevalidate = false;
    /** value for the max-age=seconds directive */
    private ?int $maxAge = null;
    /** value for the s-maxage=seconds directive */
    private ?int $sMaxAge = null;

    /**
     * enables the must-revalidate directive
     */
    public function mustRevalidate(): self
    {
        $this->mustRevalidate = true;
        return $this;
    }

    /**
     * enables the no-cache directive
     */
    public function noCache(): self
    {
        $this->noCache = true;
        return $this;
    }

    /**
     * enables the no-store directive
     */
    public function noStore(): self
    {
        $this->noStore = true;
        return $this;
    }

    /**
     * enables the no-transform directive
     */
    public function noTransform(): self
    {
        $this->noTransform = true;
        return $this;
    }


    /**
     * enables the public directive, disables the private directive
     */
    public function enablePublic(): self
    {
        $this->public  = true;
        $this->private = false;
        return $this;
    }

    /**
     * disables the private directive
     */
    public function disablePrivate(): self
    {
        $this->private = false;
        return $this;
    }

    /**
     * enables the proxy-revalidate directive
     */
    public function proxyRevalidate(): self
    {
        $this->proxyRevalidate = true;
        return $this;
    }

    /**
     * enables the max-age=seconds directive
     */
    public function maxAge(int $seconds): self
    {
        $this->maxAge = $seconds;
        return $this;
    }

    /**
     * enables the s-maxage=seconds directive
     */
    public function sMaxAge(int $seconds): self
    {
        $this->sMaxAge = $seconds;
        return $this;
    }

    /**
     * returns a string representation of the Cache-Control header value
     */
    public function __toString(): string
    {
        $values = [];
        if ($this->mustRevalidate) {
            $values[] = 'must-revalidate';
        }

        if ($this->noCache) {
            $values[] = 'no-cache';
        }

        if ($this->noStore) {
            $values[] = 'no-store';
        }

        if ($this->noTransform) {
            $values[] = 'no-transform';
        }

        if ($this->public) {
            $values[] = 'public';
        } elseif ($this->private) {
            $values[] = 'private';
        }

        if ($this->proxyRevalidate) {
            $values[] = 'proxy-revalidate';
        }

        if (null !== $this->maxAge) {
            $values[] = 'max-age=' . $this->maxAge;
        }

        if (null !== $this->sMaxAge) {
            $values[] = 's-maxage=' . $this->sMaxAge;
        }

        return join(', ', $values);
    }
}
