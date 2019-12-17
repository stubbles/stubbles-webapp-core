<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\UriPath;
/**
 * Utility methods to handle operations based on the uri called in the current request.
 *
 * @since  1.7.0
 */
class CalledUri
{
    /**
     * current uri
     *
     * @type  \stubbles\peer\http\HttpUri
     */
    private $uri;
    /**
     * current request method
     *
     * @type  string
     */
    private $method;

    /**
     * constructor
     *
     * @param   string|\stubbles\peer\http\HttpUri  $requestUri
     * @param   string                              $requestMethod
     * @throws  \InvalidArgumentException
     */
    public function __construct($requestUri, string $requestMethod)
    {
        if (empty($requestMethod)) {
            throw new \InvalidArgumentException('Request method can not be empty');
        }

        $this->uri    = HttpUri::castFrom($requestUri, 'requestUri');
        $this->method = $requestMethod;
    }

    /**
     * casts given values to an instance of UriRequest
     *
     * @param   string|\stubbles\webapp\routing\CalledUri|\stubbles\peer\http\HttpUri  $requestUri
     * @param   string                                                                 $requestMethod
     * @return  \stubbles\webapp\routing\CalledUri
     * @since   4.0.0
     */
    public static function castFrom($requestUri, string $requestMethod = null): self
    {
        if ($requestUri instanceof self) {
            return $requestUri;
        }

        return new self($requestUri, (string) $requestMethod);
    }

    /**
     * returns called method
     *
     * @return  string
     * @since   4.0.0
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * checks if request method equals given method
     *
     * @param   string  $method
     * @return  bool
     */
    public function methodEquals(string $method = null): bool
    {
        if (empty($method)) {
            return true;
        }

        return $this->method === $method;
    }

    /**
     * checks if given path is satisfied by request path
     *
     * @param   string  $expectedPath
     * @return  bool
     */
    public function satisfiesPath(string $expectedPath = null): bool
    {
        if (empty($expectedPath)) {
            return true;
        }

        if (preg_match('/^' . UriPath::pattern($expectedPath) . '/', $this->uri->path()) >= 1) {
            return true;
        }

        return false;
    }

    /**
     * checks if given method and path is satisfied by request
     *
     * @param   string  $method
     * @param   string  $expectedPath
     * @return  bool
     * @since   3.4.0
     */
    public function satisfies(string $method = null, string $expectedPath = null): bool
    {
        return $this->methodEquals($method) && $this->satisfiesPath($expectedPath);
    }

    /**
     * return path part of called uri
     *
     * @param   string  $configuredPath
     * @return  \stubbles\webapp\UriPath
     * @since   4.0.0
     */
    public function path(string $configuredPath): UriPath
    {
        return new UriPath($configuredPath, $this->uri->path());
    }

    /**
     * checks whether request was made using https
     *
     * @return  bool
     * @since   2.0.0
     */
    public function isHttps(): bool
    {
        return $this->uri->isHttps();
    }

    /**
     * transposes uri to http
     *
     * @return  \stubbles\peer\http\HttpUri
     * @since   2.0.0
     */
    public function toHttp(): HttpUri
    {
        return $this->uri->toHttp();
    }

    /**
     * transposes uri to https
     *
     * @return  \stubbles\peer\http\HttpUri
     * @since   2.0.0
     */
    public function toHttps(): HttpUri
    {
        return $this->uri->toHttps();
    }

    /**
     * returns string representation
     *
     * @return  string
     * @since   2.0.0
     */
    public function __toString(): string
    {
        return (string) $this->uri;
    }
}
