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
/**
 * Represents an error.
 *
 * @XmlTag(tagName='error')
 * @since  6.0.0
 */
class Error implements \JsonSerializable
{
    /**
     * @type  string
     */
    private $message;

    /**
     * constructor
     *
     * @param  string  $message  error message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * creates error when access to resource is forbidden
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function forbidden()
    {
        return new self('You are not allowed to access this resource.');
    }

    /**
     * creates error when access to resource is not found
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function notFound()
    {
        return new self('Requested resource could not be found.');
    }

    /**
     * creates error when access to resource with request method is not allowed
     *
     * @param   string    $requestMethod   actual request method
     * @param   string[]  $allowedMethods  list of allowed request methods
     * @return  \stubbles\webapp\response\Error
     */
    public static function methodNotAllowed($requestMethod, array $allowedMethods)
    {
        return new self(
                'The given request method '
                . strtoupper($requestMethod)
                . ' is not valid. Please use one of '
                . join(', ', $allowedMethods) . '.'
        );
    }

    /**
     * creates error when an internal server error occurs
     *
     * @param   string|\Exception  $error
     * @return  \stubbles\webapp\response\Error
     */
    public static function internalServerError($error)
    {
        return new self(
                $error instanceof \Exception ? $error->getMessage() : $error
        );
    }

    /**
     * creates error when http version used in request is not supported
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function httpVersionNotSupported()
    {
        return new self(
                'Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1.'
        );
    }

    /**
     * returns actual error message
     *
     * @return  string
     * @XmlFragment
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * returns string representation
     *
     * @return  string
     * @XmlIgnore
     */
    public function __toString()
    {
        return 'Error: ' . $this->message;
    }

    /**
     * returns a representation that can be serialized to json
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize()
    {
        return ['error' => $this->message];
    }
}
