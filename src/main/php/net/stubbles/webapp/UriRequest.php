<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\lang\BaseObject;
use net\stubbles\peer\http\HttpUri;
/**
 * Utility methods to handle operations based on the uri called in the current request.
 *
 * @since  1.7.0
 */
class UriRequest extends BaseObject
{
    /**
     * current uri
     *
     * @type  HttpUri
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
     * @param  HttpUri  $requestUri
     * @param  string   $requestMethod
     */
    public function __construct(HttpUri $requestUri, $requestMethod)
    {
        $this->uri    = $requestUri;
        $this->method = $requestMethod;
    }

    /**
     * creates an instance from request uri string
     *
     * @param   string  $requestUri
     * @param   string  $requestMethod
     * @return  UriRequest
     * @since   2.0.0
     */
    public static function fromString($requestUri, $requestMethod)
    {
        return new self(HttpUri::fromString($requestUri), $requestMethod);
    }

    /**
     * checks if request method equals given method
     *
     * @param   string  $method
     * @return  bool
     */
    public function methodEquals($method)
    {
        if (empty($method)) {
            return true;
        }

        return $this->method === $method;
    }

    /**
     * checks if given path is satisfied by request path
     *
     * @param   string  $path
     * @return  bool
     */
    public function satisfiesPath($pathPattern)
    {
        if (preg_match('/^' . $pathPattern . '$/', $this->uri->getPath()) >= 1) {
            return true;
        }

        return false;
    }

    /**
     * gets path arguments from uri
     *
     * @param   string  $pathPattern
     * @return  string[]
     */
    public function getPathArguments($pathPattern)
    {
        $matches = array();
        preg_match('/^' . $pathPattern . '$/', $this->uri->getPath(), $matches);
        array_shift($matches);
        return $matches;
    }

    /**
     * checks whether request was made using https
     *
     * @return  bool
     * @since   2.0.0
     */
    public function isHttps()
    {
        return $this->uri->isHttps();
    }

    /**
     * transposes uri to http
     *
     * @return  HttpUri
     * @since   2.0.0
     */
    public function toHttp()
    {
        return $this->uri->toHttp();
    }

    /**
     * transposes uri to https
     *
     * @return  HttpUri
     * @since   2.0.0
     */
    public function toHttps()
    {
        return $this->uri->toHttps();
    }

    /**
     * returns string representation
     *
     * @return  string
     * @since   2.0.0
     */
    public function __toString()
    {
        return (string) $this->uri;
    }
}
?>