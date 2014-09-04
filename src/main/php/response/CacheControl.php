<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
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
    /**
     * header name
     */
    const HEADER_NAME        = 'Cache-Control';
    /**
     * whether the must-revalidate directive is enabled
     *
     * @type  bool
     */
    private $mustRevalidate  = false;
    /**
     * whether the no-cache directive is enabled
     *
     * @type  bool
     */
    private $noCache         = false;
    /**
     * whether the no-store directive is enabled
     *
     * @type  bool
     */
    private $noStore         = false;
    /**
     * whether the no-transform directive is enabled
     *
     * @type  bool
     */
    private $noTransform     = false;
    /**
     * whether the public directive is enabled
     *
     * @type  bool
     */
    private $public          = false;
    /**
     * whether the private directive is enabled
     *
     * @type  bool
     */
    private $private         = true;
    /**
     * whether the proxy-revalidate directive is enabled
     *
     * @type  bool
     */
    private $proxyRevalidate = false;
    /**
     * value for the max-age=seconds directive
     *
     * @type  int
     */
    private $maxAge          = null;
    /**
     * value for the s-maxage=seconds directive
     *
     * @type  int
     */
    private $sMaxAge         = null;

    /**
     * enables the must-revalidate directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function mustRevalidate()
    {
        $this->mustRevalidate = true;
        return $this;
    }

    /**
     * enables the no-cache directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function noCache()
    {
        $this->noCache = true;
        return $this;
    }

    /**
     * enables the no-store directive
     *
     * @return \stubbles\webapp\response\CacheControl
     */
    public function noStore()
    {
        $this->noStore = true;
        return $this;
    }

    /**
     * enables the no-transform directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function noTransform()
    {
        $this->noTransform = true;
        return $this;
    }


    /**
     * enables the public directive, disables the private directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function enablePublic()
    {
        $this->public  = true;
        $this->private = false;
        return $this;
    }

    /**
     * disables the private directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function disablePrivate()
    {
        $this->private = false;
        return $this;
    }

    /**
     * enables the proxy-revalidate directive
     *
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function proxyRevalidate()
    {
        $this->proxyRevalidate = true;
        return $this;
    }

    /**
     * enables the max-age=seconds directive
     *
     * @param   int  $seconds
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function maxAge($seconds)
    {
        $this->maxAge = $seconds;
        return $this;
    }

    /**
     * enables the s-maxage=seconds directive
     *
     * @param   int  $seconds
     * @return  \stubbles\webapp\response\CacheControl
     */
    public function sMaxAge($seconds)
    {
        $this->sMaxAge = $seconds;
        return $this;
    }

    /**
     * returns a string representation of the Cache-Control header value
     *
     * @return  string
     */
    public function __toString()
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
