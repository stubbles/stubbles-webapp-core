<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
use net\stubbles\peer\http\HttpUri;
/**
 * List of response headers.
 *
 * @since  3.5.0
 */
class Headers implements \IteratorAggregate
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
     * @param   string  $value
     * @return  Headers
     */
    public function add($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * adds location header with given uri
     *
     * @param   string|HttpUri  $uri
     * @return  Headers
     */
    public function location($uri)
    {
        return $this->add('Location', (($uri instanceof HttpUri) ? ($uri->asStringWithNonDefaultPort()) : ($uri)));
    }

    /**
     * adds allow header with list of allowed methods
     *
     * @param   string[]  $allowedMethods
     * @return  Headers
     */
    public function allow(array $allowedMethods)
    {
        return $this->add('Allow', join(', ', $allowedMethods));
    }

    /**
     * adds non-standard acceptable header with list of supported mime types
     *
     * @param   string[]  $supportedMimeTypes
     * @return  Headers
     */
    public function acceptable(array $supportedMimeTypes)
    {
        if (count($supportedMimeTypes) > 0) {
            $this->add('X-Acceptable', join(', ', $supportedMimeTypes));
        }

        return $this;
    }

    /**
     * checks if header with given name is present
     *
     * @param   string  $name
     * @return  bool
     */
    public function contain($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * returns an external iterator
     *
     * @return  Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }
}
