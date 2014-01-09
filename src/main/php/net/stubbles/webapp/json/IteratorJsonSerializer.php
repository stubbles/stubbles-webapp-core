<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\json;
use net\stubbles\lang\exception\IllegalArgumentException;
/**
 * Serializer which is capable of transforming iterators to json.
 *
 * An iterator can be serialized into a JSON object if the keys are either
 * strings or objects that can be transformed into a string. Otherwise the
 * iterator will be serialized to an array. Please note that an according check
 * is only done for the first key, all other keys will be treated the same.
 *
 * @since  3.2.0
 */
class IteratorJsonSerializer implements ObjectJsonSerializer
{
    /**
     * serializes given value
     *
     * @param   mixed           $object
     * @param   JsonSerializer  $jsonSerializer
     * @return  string
     * @throws  IllegalArgumentException
     */
    public function serialize($object, JsonSerializer $jsonSerializer)
    {
        if (!($object instanceof \Iterator)) {
            throw new IllegalArgumentException('Given object is not an instance of \Iterator');
        }

        $serialize    = null;
        $parenthesize = null;
        $values       = [];
        foreach ($object as $key => $value) {
            if (null === $serialize) {
                $serialize    = $this->getSerializer($key, $jsonSerializer);
                $parenthesize = $this->getParenthesize($key);
            }

            $values[] = $serialize($key, $value);
        }

        // iterator didn't contain any elements, serialize as empty array
        if (null === $parenthesize) {
            return json_encode([]);
        }

        return $parenthesize(join(',', $values));
    }

    /**
     * creates a function which can serialize iterator values depending on key
     *
     * @param   mixed           $key
     * @param   JsonSerializer  $jsonSerializer
     * @return  \Closure
     */
    private function getSerializer($key, JsonSerializer $jsonSerializer)
    {
        if ($this->isAllowed($key)) {
            return function($key, $value) use ($jsonSerializer)
            {
                return '"' . $key . '":' . $jsonSerializer->serialize($value);
            };
        }

        return function($key, $value) use ($jsonSerializer)
        {
            return $jsonSerializer->serialize($value);
        };
    }

    /**
     * creates a function which puts the result in correct parenthesis
     *
     * @param   mixed  $key
     * @return  \Closure
     */
    private function getParenthesize($key)
    {
        if ($this->isAllowed($key)) {
            return function($result)
            {
                return '{' . $result . '}';
            };
        }

        return function($result)
        {
            return '[' . $result . ']';
        };
    }

    /**
     * checks whether given key is allowed to be used as key in JSON
     *
     * @param   mixed  $key
     * @return  bool
     */
    private function isAllowed($key)
    {
        return is_string($key) || (is_object($key) && method_exists($key, '__toString'));
    }
}
