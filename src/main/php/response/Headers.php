<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use IteratorAggregate;
use stubbles\peer\http\HttpUri;
use Traversable;

/**
 * List of response headers.
 *
 * @since  4.0.0
 * @implements  IteratorAggregate<string,mixed>
 * @implements  ArrayAccess<string,mixed>
 */
class Headers implements IteratorAggregate, ArrayAccess
{
    /**
     * list of headers for this response
     *
     * @var  array<string,mixed>
     */
    private array $headers = [];

    /**
     * adds header with given name
     */
    public function add(string $name, mixed $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * adds X-Request-ID with given value
     *
     * @since  5.1.0
     * @see    https://devcenter.heroku.com/articles/http-request-id
     */
    public function requestId(string $requestId): self
    {
        return $this->add('X-Request-ID', $requestId);
    }

    /**
     * adds location header with given uri
     */
    public function location(string|HttpUri $uri): self
    {
        return $this->add(
            'Location',
            (($uri instanceof HttpUri) ? ($uri->asStringWithNonDefaultPort()) : ($uri))
        );
    }

    /**
     * adds allow header with list of allowed methods
     *
     * @param  string[]  $allowedMethods
     */
    public function allow(array $allowedMethods): self
    {
        return $this->add('Allow', join(', ', $allowedMethods));
    }

    /**
     * adds non-standard acceptable header with list of supported mime types
     *
     * @param  string[]  $supportedMimeTypes
     */
    public function acceptable(array $supportedMimeTypes): self
    {
        if (!empty($supportedMimeTypes)) {
            $this->add('X-Acceptable', join(', ', $supportedMimeTypes));
        }

        return $this;
    }

    /**
     * enforce a download and suggest given file name
     */
    public function forceDownload(string $filename): self
    {
        return $this->add(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $filename)
        );
    }

    /**
     * enables the Cache-Control header
     *
     * If no specific directives are enabled the value will be
     * <code>Cache-Control: private</code>
     * by default.
     *
     * @see    http://tools.ietf.org/html/rfc7234#section-5.2
     * @since  5.1.0
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
     * @see    http://tools.ietf.org/html/rfc7234#section-5.1
     * @since  5.1.0
     */
    public function age(int $seconds): self
    {
        return $this->add('Age', $seconds);
    }

    /**
     * checks if header with given name is present
     *
     * Please note that header names are treated case sensitive.
     */
    public function contain(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<string,mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->headers);
    }

    /**
     * checks if header with given name is present
     *
     * Please note that header names are treated case sensitive.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->contain($offset);
    }

    /**
     * returns header with given name
     */
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->contain($offset)) {
            return $this->headers[$offset];
        }

        return null;
    }

    /**
     * adds header with given name
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->add($offset, $value);
    }

   /**
    * removes header with given name
    *
    * @throws  BadMethodCallException
    */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Removing headers is not supported');
    }
}
