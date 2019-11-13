<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
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
     * returns mime type for response body
     *
     * @return  \stubbles\webapp\response\mimetypes\MimeType
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
     *
     * @param   int     $statusCode
     * @param   string  $reasonPhrase  optional
     * @return  \stubbles\webapp\Response
     */
    public function setStatusCode(int $statusCode, string $reasonPhrase = null): self;

    /**
     * provide direct access to set a status code
     *
     * @return  \stubbles\webapp\response\Status
     * @since   5.1.0
     */
    public function status(): Status;

    /**
     * add a header to the response
     *
     * @param   string  $name   the name of the header
     * @param   string  $value  the value of the header
     * @return  \stubbles\webapp\Response
     */
    public function addHeader(string $name, string $value): Response;

    /**
     * returns list of headers
     *
     * @return  \stubbles\webapp\response\Headers
     * @since   4.0.0
     */
    public function headers(): Headers;

    /**
     * add a cookie to the response
     *
     * @param   \stubbles\webapp\response\Cookie  $cookie  the cookie to set
     * @return  \stubbles\webapp\Response
     */
    public function addCookie(Cookie $cookie): self;

    /**
     * removes cookie with given name
     *
     * @param   string  $name
     * @return  \stubbles\webapp\Response
     * @since   2.0.0
     */
    public function removeCookie(string $name): self;

    /**
     * write body into the response
     *
     * @param   string  $body
     * @return  \stubbles\webapp\Response
     */
    public function write($body): self;

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
     * @return  bool
     * @since   3.1.0
     */
    public function isFixed(): bool;

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri         http uri to redirect to
     * @param   int                                 $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @since   1.3.0
     */
    public function redirect($uri, int $statusCode = 302);

    /**
     * creates a 403 Forbidden message
     *
     * @return  \stubbles\webapp\response\Error
     * @since   2.0.0
     */
    public function forbidden(): Error;

    /**
     * creates a 404 Not Found message into
     *
     * @return  \stubbles\webapp\response\Error
     * @since   2.0.0
     */
    public function notFound(): Error;

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  \stubbles\webapp\response\Error
     * @since   2.0.0
     */
    public function methodNotAllowed(string $requestMethod, array $allowedMethods): Error;

    /**
     * creates a 406 Not Acceptable message
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @since   2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = []);

    /**
     * creates a 500 Internal Server Error message
     *
     * @param   string|\Exception  $error
     * @return  \stubbles\webapp\response\Error
     * @since   2.0.0
     */
    public function internalServerError($error): Error;

    /**
     * creates a 505 HTTP Version Not Supported message
     *
     * @since   2.0.0
     */
    public function httpVersionNotSupported();
}
