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
use net\stubbles\lang\reflect\BaseReflectionClass;
use net\stubbles\lang\reflect\annotation\Annotatable;
/**
 * Serializes arbitrary objects based on annotations.
 *
 * @since  3.2.0
 */
class AnnotationBasedObjectJsonSerializer implements ObjectJsonSerializer
{
    /**
     * map of properties to serialize
     *
     * @type  array
     */
    private $properties  = [];
    /**
     * map of methods to serialize
     *
     * @type  array
     */
    private $methods     = [];
    /**
     * reflection instance of class to serialize
     *
     * @type  BaseReflectionClass
     */
    private $refClass;
    /**
     * the matcher to be used for methods and properties
     *
     * @type  JsonSerializerMethodPropertyMatcher
     */
    private static $methodAndPropertyMatcher;
    /**
     * simple cache
     *
     * @type  array
     */
    private static $cache = array();

    /**
     * static initializer
     */
    public static function __static()
    {
        self::$methodAndPropertyMatcher = new JsonSerializerMethodPropertyMatcher();
    }

    /**
     * constructor
     *
     * It is recommended to not use the constructor but the static forClass()
     * method. The constructor should be used if one is sure that there is only
     * one instance of a class to serialize.
     *
     * @param  BaseReflectionClass  $objectClass
     */
    public function __construct(BaseReflectionClass $objectClass)
    {
        $this->refClass = $objectClass;
        $this->extractProperties();
        $this->extractMethods();
    }

    /**
     * creates the structure from given object
     *
     * This method will cache the result - on the next request with the same
     * class it will return the same result, even if the given object is a
     * different instance.
     *
     * @param   BaseReflectionClass  $objectClass
     * @return  AnnotationBasedObjectJsonSerializer
     */
    public static function forClass(BaseReflectionClass $objectClass)
    {
        $className = $objectClass->getName();
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        self::$cache[$className] = new self($objectClass);
        return self::$cache[$className];
    }

    /**
     * serializes given value
     *
     * @param   mixed           $object
     * @param   JsonSerializer  $jsonSerializer
     * @return  string
     */
    public function serialize($object, JsonSerializer $jsonSerializer)
    {
        $result = '{';
        foreach ($this->properties as $propertyName => $key) {
            $result .= '"' . $key . '":' . $jsonSerializer->serialize($object->$propertyName);
        }

        foreach ($this->methods as $methodName => $key) {
            $result .= '"' . $key . '":' . $jsonSerializer->serialize($object->$methodName());
        }

        return $result . '}';
    }

    /**
     * extract informations about properties
     */
    private function extractProperties()
    {
        foreach ($this->refClass->getPropertiesByMatcher(self::$methodAndPropertyMatcher) as $property) {
            $this->properties[$property->getName()] = $this->createKey($property, $property->getName());
        }
    }

    /**
     * extract informations about methods
     */
    private function extractMethods()
    {
        foreach ($this->refClass->getMethodsByMatcher(self::$methodAndPropertyMatcher) as $method) {
            $this->methods[$method->getName()] = $this->createKey($method, $method->getName());
        }
    }

    /**
     * extracts informations about annotated element
     *
     * @param   Annotatable  $annotatable      the annotatable element to serialize
     * @param   string       $annotatableName  name of annotatable element
     * @return  string
     */
    private function createKey(Annotatable $annotatable, $annotatableName)
    {
        if ($annotatable->hasAnnotation('JsonKey')) {
            return $annotatable->getAnnotation('JsonKey')->getValue();
        }

        return $annotatableName;
    }
}
AnnotationBasedObjectJsonSerializer::__static();
