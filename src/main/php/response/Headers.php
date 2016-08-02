<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\HttpUri;
/**
 * List of response headers.
 *
 * @since  4.0.0
 */
class Headers implements \IteratorAggregate, \ArrayAccess
{
    /**
     * list of headers for this response
     *
     * @type  array
     */
    private $headers = [];

    /**
     * adds header with given name
     *
     * @param   string  $name
     * @param   mixed   $value
     * @return  \stubbles\webapp\response\Headers
     */
    public function add(string $name, $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * adds X-Request-ID with given value
     *
     * @param   string  $requestId
     * @return  \stubbles\webapp\response\Headers
     * @since   5.1.0
     * @see     https://devcenter.heroku.com/articles/http-request-id
     */
    public function requestId(string $requestId): self
    {
        return $this->add('X-Request-ID', $requestId);
    }

    /**
     * adds location header with given uri
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri
     * @return  \stubbles\webapp\response\Headers
     */
    public function location($uri): self
    {
        return $this->add(
                'Location',
                (($uri instanceof HttpUri) ? ($uri->asStringWithNonDefaultPort()) : ($uri))
        );
    }

    /**
     * adds allow header with list of allowed methods
     *
     * @param   string[]  $allowedMethods
     * @return  \stubbles\webapp\response\Headers
     */
    public function allow(array $allowedMethods): self
    {
        return $this->add('Allow', join(', ', $allowedMethods));
    }

    /**
     * adds non-standard acceptable header with list of supported mime types
     *
     * @param   string[]  $supportedMimeTypes
     * @return  \stubbles\webapp\response\Headers
     */
    public function acceptable(array $supportedMimeTypes): self
    {
        if (count($supportedMimeTypes) > 0) {
            $this->add('X-Acceptable', join(', ', $supportedMimeTypes));
        }

        return $this;
    }

    /**
     * enforce a download and suggest given file name
     *
     * @param   string  $filename
     * @return  \stubbles\webapp\response\Headers
     */
    public function forceDownload(string $filename): self
    {
        return $this->add('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * enables the Cache-Control header
     *
     * If no specific directives are enabled the value will be
     * <code>Cache-Control: private</code>
     * by default.
     *
     * @return  \stubbles\webapp\response\CacheControl
     * @see     http://tools.ietf.org/html/rfc7234#section-5.2
     * @since   5.1.0
     */
    public function cacheControl(): CacheControl
    {
        $cacheControl = new CacheControl();
        $this->add(CacheControl::HEADER_NAME, $cacheControl);
        return $cacheControl;
    }

    /**
     * adds Age header with given amount of seconds
     *
     * @param   int  $seconds
     * @return  \stubbles\webapp\response\Headers
     * @see     http://tools.ietf.org/html/rfc7234#section-5.1
     * @since   5.1.0
     */
    public function age(int $seconds): self
    {
        return $this->add('Age', $seconds);
    }

    /**
     * checks if header with given name is present
     *
     * Please note that header names are treated case sensitive.
     *
     * @param   string  $name  name of header to check for
     * @return  bool
     */
    public function contain(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * returns an external iterator
     *
     * @return  \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->headers);
    }

    /**
     * checks if header with given name is present
     *
     * Please note that header names are treated case sensitive.
     *
     * @param   string  $offset  name of header to check for
     * @return  bool
     */
    public function offsetExists($offset)
    {
        return $this->contain($offset);
    }

    /**
     * returns header with given name
     *
     * @param   string  $offset  name of header to retrieve
     * @return  string
     */
    public function offsetGet($offset)
    {
        if ($this->contain($offset)) {
            return $this->headers[$offset];
        }

        return null;
    }

    /**
     * adds header with given name
     *
     * @param  string  $offset
     * @param  string  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

   /**
    * removes header with given name
    *
    * @param   string  $offset
    * @throws  \BadMethodCallException
    */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Removing headers is not supported');
    }
}
