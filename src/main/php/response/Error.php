<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use Exception;
use JsonSerializable;
use stubbles\input\errors\ParamErrors;
use stubbles\input\errors\messages\ParamErrorMessages;
use stubbles\sequence\Sequence;
/**
 * Represents an error.
 *
 * @XmlTag(tagName='error')
 * @since  6.0.0
 */
class Error implements JsonSerializable
{
    public function __construct(private mixed $message, private string $type = 'Error') { }

    /**
     * creates error when access to resource is unauthorized
     *
     * @since  8.0.0
     */
    public static function unauthorized(): self
    {
        return new self(
            'You need to authenticate to access this resource.',
            'Unauthorized'
        );
    }

    /**
     * creates error when access to resource is forbidden
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
     */
    public static function methodNotAllowed(
        string $requestMethod,
        array $allowedMethods
    ): self {
        return new self(
            sprintf(
                'The given request method %s is not valid. Please use one of %s.',
                strtoupper($requestMethod),
                join(', ', $allowedMethods)
            ),
            'Method Not Allowed',
        );
    }

    /**
     * creates error when an internal server error occurs
     */
    public static function internalServerError(string|Exception $error): self
    {
        return new self(
            $error instanceof Exception ? $error->getMessage() : $error,
            'Internal Server Error'
        );
    }

    /**
     * creates error when http version used in request is not supported
     */
    public static function httpVersionNotSupported(): self
    {
        return new self(
            'Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1.'
        );
    }

    /**
     * creates error with given list of param errors
     *
     * @since  6.2.0
     */
    public static function inParams(
        ParamErrors $errors,
        ParamErrorMessages $errorMessages
    ): self {
        return new self(
            Sequence::of($errors)->map(
                function(array $errors, string $paramName) use ($errorMessages): array
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
            )
        );
    }

    /**
     * returns error type
     *
     * @XmlAttribute(attributeName='type')
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * returns actual error message
     *
     * @XmlFragment
     */
    public function message(): mixed
    {
        return $this->message;
    }

    /**
     * returns string representation
     * @XmlIgnore
     */
    public function __toString(): string
    {
        return $this->type . ': ' . $this->message;
    }

    /**
     * returns a representation that can be serialized to json
     *
     * @return  array<string,string>
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        return ['error' => $this->message];
    }
}
