<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;

use Exception;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\response\Cookie;
use stubbles\webapp\response\Error;
use stubbles\webapp\response\Headers;
use stubbles\webapp\response\SendableResponse;
use stubbles\webapp\response\Status;
use stubbles\webapp\response\mimetypes\MimeType;
/**
 * Interface for a response to a request.
 *
 * The response collects all data that should be send to the source
 * that initiated the request.
 */
interface Response extends SendableResponse
{
    /**
     * adjusts mime type of response to given mime type
     */
    public function adjustMimeType(MimeType $mimeType): self;

    /**
     * returns mime type for response body
     */
    public function mimeType(): MimeType;

    /**
     * sets the status code to be send
     *
     * This needs only to be done if another status code then the default one
     * 200 OK should be send.
     *
     * If reason phrase is null it will use the default reason phrase for given
     * status code.
     */
    public function setStatusCode(int $statusCode, string $reasonPhrase = null): self;

    /**
     * provide direct access to set a status code
     *
     * @since  5.1.0
     */
    public function status(): Status;

    /**
     * add a header to the response
     */
    public function addHeader(string $name, string $value): Response;

    /**
     * returns list of headers
     *
     * @since  4.0.0
     */
    public function headers(): Headers;

    /**
     * add a cookie to the response
     */
    public function addCookie(Cookie $cookie): self;

    /**
     * removes cookie with given name
     *
     * @since  2.0.0
     */
    public function removeCookie(string $name): self;

    /**
     * write body into the response
     */
    public function write(mixed $body): self;

    /**
     * a response is fixed when a final status has been set
     *
     * A final status is set when one of the following methods is called:
     * - forbidden()
     * - notFound()
     * - methodNotAllowed()
     * - notAcceptable()
     * - internalServerError()
     * - httpVersionNotSupported()
     *
     * @since  3.1.0
     */
    public function isFixed(): bool;

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @since  1.3.0
     */
    public function redirect(string|HttpUri $uri, int $statusCode = 302): void;

    /**
     * creates a 401 Unauthorized message including a WWW-Authenticate header with given challenge
     *
     * @param  string[]  $challenges
     * @since  8.0.0
     */
    public function unauthorized(array $challenges): Error;

    /**
     * creates a 403 Forbidden message
     *
     * @since  2.0.0
     */
    public function forbidden(): Error;

    /**
     * creates a 404 Not Found message into
     *
     * @since  2.0.0
     */
    public function notFound(): Error;

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param  string[]  $allowedMethods
     * @since  2.0.0
     */
    public function methodNotAllowed(string $requestMethod, array $allowedMethods): Error;

    /**
     * creates a 406 Not Acceptable message
     *
     * @param  string[]  $supportedMimeTypes  list of supported mime types
     * @since  2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = []): void;

    /**
     * creates a 500 Internal Server Error message
     *
     * @since  2.0.0
     */
    public function internalServerError(string|Exception $error): Error;

    /**
     * creates a 505 HTTP Version Not Supported message
     *
     * @since  2.0.0
     */
    public function httpVersionNotSupported(): void;
}
