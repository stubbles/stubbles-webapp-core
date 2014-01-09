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
use net\stubbles\ioc\Injector;
use net\stubbles\lang;
/**
 * Serializes arbitrary data except resources to json.
 *
 * @since  3.2.0
 */
class JsonSerializer
{
    /**
     * injector to create object serializer instances
     *
     * @type  Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  Injector  $injector
     * @Inject
     */
    public function  __construct(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * serializes given value to json
     *
     * @param   mixed  $value
     * @return  string
     */
    public function serialize($value)
    {
        switch (gettype($value)) {
            case 'boolean':
            case 'string':
            case 'integer':
            case 'double':
                return $this->serializeValue($value);

            case 'array':
                // ensure that single array elements are treated properly, could be object instances
                return $this->serializeObject(new \ArrayIterator($value));

            case 'object':
                return $this->serializeObject($value);

            default:
                return "null";
        }
    }

    /**
     * serializes a scalar or array value to json
     *
     * @param   scalar|array  $value
     * @return  string
     */
    public function serializeValue($value)
    {
        return json_encode($value);
    }

    /**
     * serializes an object to json
     *
     * @param   object  $object  object to serialize
     * @return  string
      */
    public function serializeObject($object)
    {
        return $this->getObjectSerializer($object)->serialize($object, $this);
    }

    /**
     * returns serializer for given object
     *
     * @param   object  $object
     * @return  JsonObjectSerializer
     */
    private function getObjectSerializer($object)
    {
        if ($object instanceof \Iterator) {
            return new IteratorJsonSerializer();
        }

        $objectClass = lang\reflect($object);
        if (!$objectClass->hasAnnotation('JsonSerializer')) {
            return AnnotationBasedObjectJsonSerializer::forClass($objectClass);
        }

        return $this->injector->getInstance($objectClass->getAnnotation('JsonSerializer')
                                                        ->getSerializerClass()
                                                        ->getName()
        );
    }
}
