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
    private $requestUri;
    /**
     * condition which lead to selection of processor
     *
     * @type  string
     */
    private $processorUriCondition = "^/";

    /**
     * constructor
     *
     * @param  HttpUri  $requestUri
     */
    public function __construct(HttpUri $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * creates an instance from request uri string
     *
     * @param   string  $requestUri
     * @return  UriRequest
     * @since   2.0.0
     */
    public static function fromString($requestUri)
    {
        return new self(HttpUri::fromString($requestUri));
    }

    /**
     * returns path of request uri
     *
     * @return  string
     * @since   2.0.0
     */
    public function getPath()
    {
        return $this->requestUri->getPath();
    }

    /**
     * checks if current uri satisfies given uri condition
     *
     * @param   string  $uriCondition  uri pattern to check
     * @return  bool
     */
    public function satisfies($uriCondition)
    {
        if (null == $uriCondition || preg_match('~' . $uriCondition . '~', $this->requestUri->getPath()) === 1) {
            return true;
        }

        return false;
    }

    /**
     * sets condition which lead to selection of processor
     *
     * @param   string  $uriCondition
     * @return  UriRequest
     */
    public function setProcessorUriCondition($uriCondition)
    {
        $this->processorUriCondition = $uriCondition;
        return $this;
    }

    /**
     * returns part of the uri which was responsible for selection of processor
     *
     * @return  string
     */
    public function getProcessorUri()
    {
        $matches = array();
        preg_match('~(' . $this->processorUriCondition . ')(.*)?~', $this->requestUri->getPath(), $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }

        return '';
    }

    /**
     * returns remaining uri which was not part of decision for selecting the processor
     *
     * @param   string  $fallback  optional  return this if no remaining uri present
     * @return  string
     */
    public function getRemainingUri($fallback = '')
    {
        $matches = array();
        preg_match('~(' . $this->processorUriCondition . ')([^?]*)?~', $this->requestUri->getPath(), $matches);
        if (isset($matches[2]) && !empty($matches[2])) {
            return $matches[2];
        }

        return $fallback;
    }

    /**
     * checks whether request was made using ssl
     *
     * @return  bool
     * @since   2.0.0
     */
    public function isSsl()
    {
        return $this->requestUri->isHttps();
    }

    /**
     * transposes uri to http
     *
     * @return  HttpUri
     * @since   2.0.0
     */
    public function toHttp()
    {
        return $this->requestUri->toHttp();
    }

    /**
     * transposes uri to https
     *
     * @return  HttpUri
     * @since   2.0.0
     */
    public function toHttps()
    {
        return $this->requestUri->toHttps();
    }

    /**
     * returns string representation
     *
     * @return  string
     * @since   2.0.0
     */
    public function __toString()
    {
        return (string) $this->requestUri;
    }
}
?>