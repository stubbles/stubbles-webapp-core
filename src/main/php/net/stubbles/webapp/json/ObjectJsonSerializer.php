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
/**
 * Interface for object to json serializers.
 *
 * @since  3.2.0
 */
interface ObjectJsonSerializer
{
    /**
     * serializes given value
     *
     * @param   mixed           $object
     * @param   JsonSerializer  $jsonSerializer
     * @return  string
     */
    public function serialize($object, JsonSerializer $jsonSerializer);
}
