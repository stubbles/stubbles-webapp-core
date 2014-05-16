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
use net\stubbles\peer\http\HttpUri;
/**
 * Utility methods to handle operations based on the uri called in the current request.
 *
 * @since  1.7.0
 */
class UriRequest
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
     * @param   string  $expectedPath
     * @return  bool
     */
    public function satisfiesPath($expectedPath)
    {
        if (empty($expectedPath)) {
            return true;
        }

        if (preg_match('/^' . $this->createPathPattern($expectedPath) . '/', $this->uri->getPath()) >= 1) {
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
    public function satisfies($method, $expectedPath)
    {
        return $this->methodEquals($method) && $this->satisfiesPath($expectedPath);
    }

    /**
     * gets path arguments from uri
     *
     * @param   string  $expectedPath
     * @return  string[]
     */
    public function getPathArguments($expectedPath)
    {
        $arguments = array();
        preg_match('/^' . $this->createPathPattern($expectedPath) . '/', $this->uri->getPath(), $arguments);
        array_shift($arguments);
        $names  = array();
        $result = array();
        preg_match_all('/[{][^}]*[}]/', str_replace('/', '\/', $expectedPath), $names);
        foreach ($names[0] as $key => $name) {
            if (isset($arguments[$key])) {
                $result[str_replace(array('{', '}'), '', $name)] = $arguments[$key];
            }
        }

        return $result;
    }

    /**
     * returns remaining path if there is any
     *
     * @param   string  $expectedPath
     * @return  string
     */
    public function getRemainingPath($expectedPath)
    {
        $matches = array();
        preg_match('/(' . $this->createPathPattern($expectedPath) . ')([^?]*)?/', $this->uri->getPath(), $matches);
        $last = count($matches) - 1;
        if (2 > $last) {
            return null;
        }

        if (isset($matches[$last]) && !empty($matches[$last])) {
            return $matches[$last];
        }

        return null;
    }

    /**
     * creates a pattern for given path
     *
     * @param   string  $path
     * @return  string
     */
    private function createPathPattern($path)
    {
        return preg_replace('/[{][^}]*[}]/', '([^\/]+)', str_replace('/', '\/', $path));
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
