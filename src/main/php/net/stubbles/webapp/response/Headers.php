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
