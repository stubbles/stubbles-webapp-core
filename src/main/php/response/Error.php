<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\input\errors\ParamErrors;
use stubbles\input\errors\messages\ParamErrorMessages;
use stubbles\sequence\Sequence;
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
    private $type;
    /**
     * @type  string
     */
    private $message;

    /**
     * constructor
     *
     * @param  mixed   $message  error message
     * @param  string  $type     error type     optional
     */
    public function __construct($message, string $type = 'Error')
    {
        $this->message = $message;
        $this->type    = $type;
    }

    /**
     * creates error when access to resource is forbidden
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function forbidden(): self
    {
        return new self(
                'You are not allowed to access this resource.',
                'Forbidden'
        );
    }

    /**
     * creates error when access to resource is not found
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function notFound(): self
    {
        return new self('Requested resource could not be found.', 'Not Found');
    }

    /**
     * creates error when access to resource with request method is not allowed
     *
     * @param   string    $requestMethod   actual request method
     * @param   string[]  $allowedMethods  list of allowed request methods
     * @return  \stubbles\webapp\response\Error
     */
    public static function methodNotAllowed(string $requestMethod, array $allowedMethods): self
    {
        return new self(
                'The given request method '
                . strtoupper($requestMethod)
                . ' is not valid. Please use one of '
                . join(', ', $allowedMethods) . '.',
                'Method Not Allowed'
        );
    }

    /**
     * creates error when an internal server error occurs
     *
     * @param   string|\Exception  $error
     * @return  \stubbles\webapp\response\Error
     */
    public static function internalServerError($error): self
    {
        return new self(
                $error instanceof \Exception ? $error->getMessage() : $error,
                'Internal Server Error'
        );
    }

    /**
     * creates error when http version used in request is not supported
     *
     * @return  \stubbles\webapp\response\Error
     */
    public static function httpVersionNotSupported(): self
    {
        return new self(
                'Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1.'
        );
    }

    /**
     * returns error type
     *
     * @return  string
     * @XmlAttribute(attributeName='type')
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * returns actual error message
     *
     * @return  string
     * @XmlFragment
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * creates error with given list of param errors
     *
     * @param   \stubbles\input\errors\ParamErrors            $errors
     * @param   \stubbles\webapp\response\ParamErrorMessages  $errorMessages
     * @return  self
     * @since   6.2.0
     */
    public static function inParams(ParamErrors $errors, ParamErrorMessages $errorMessages): self
    {
        return new self(Sequence::of($errors)->map(
                function(array $errors, $paramName) use ($errorMessages): array
                {
                    $resolved = ['field' => $paramName, 'errors' => []];
                    foreach ($errors as $id => $error) {
                        $resolved['errors'][] = [
                                'id'      => $id,
                                'details' => $error->details(),
                                'message' => $errorMessages->messageFor($error)->message()
                        ];
                    }

                    return $resolved;
                }
        ));
    }

    /**
     * returns string representation
     *
     * @return  string
     * @XmlIgnore
     */
    public function __toString(): string
    {
        return $this->type . ': ' . $this->message;
    }

    /**
     * returns a representation that can be serialized to json
     *
     * @return  array
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['error' => $this->message];
    }
}
