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
     * @param   string  $value
     * @return  \stubbles\webapp\response\Headers
     */
    public function add($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * adds location header with given uri
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri
     * @return  \stubbles\webapp\response\Headers
     */
    public function location($uri)
    {
        return $this->add('Location', (($uri instanceof HttpUri) ? ($uri->asStringWithNonDefaultPort()) : ($uri)));
    }

    /**
     * adds allow header with list of allowed methods
     *
     * @param   string[]  $allowedMethods
     * @return  \stubbles\webapp\response\Headers
     */
    public function allow(array $allowedMethods)
    {
        return $this->add('Allow', join(', ', $allowedMethods));
    }

    /**
     * adds non-standard acceptable header with list of supported mime types
     *
     * @param   string[]  $supportedMimeTypes
     * @return  \stubbles\webapp\response\Headers
     */
    public function acceptable(array $supportedMimeTypes)
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
    public function forceDownload($filename)
    {
        return $this->add('Content-Disposition', 'attachment; filename=' . $filename);
    }

    /**
     * checks if header with given name is present
     *
     * Please note that header names are treated case sensitive.
     *
     * @param   string  $name  name of header to check for
     * @return  bool
     */
    public function contain($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * returns an external iterator
     *
     * @return  \Traversable
     */
    public function getIterator()
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
