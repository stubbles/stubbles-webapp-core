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
 * Interface for a response to a request.
 *
 * The response collects all data that should be send to the source
 * that initiated the request.
 */
interface Response
{
    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear();

    /**
     * sets the status code to be send
     *
     * This needs only to be done if another status code then the default one
     * 200 OK should be send.
     *
     * @param   int  $statusCode
     * @return  Response
     */
    public function setStatusCode($statusCode);

    /**
     * add a header to the response
     *
     * @param   string  $name   the name of the header
     * @param   string  $value  the value of the header
     * @return  Response
     */
    public function addHeader($name, $value);

    /**
     * returns list of headers
     *
     * @return  Headers
     * @since   4.0.0
     */
    public function headers();

    /**
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie);

    /**
     * removes cookie with given name
     *
     * @param   string  $name
     * @return  Response
     * @since   2.0.0
     */
    public function removeCookie($name);

    /**
     * write body into the response
     *
     * @param   string  $body
     * @return  Response
     */
    public function write($body);

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
    public function isFixed();

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string|HttpUri  $uri         http uri to redirect to
     * @param   int             $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  Response
     * @since   1.3.0
     */
    public function redirect($uri, $statusCode = 302);

    /**
     * creates a 403 Forbidden message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function forbidden();

    /**
     * creates a 404 Not Found message into
     *
     * @return  Response
     * @since   2.0.0
     */
    public function notFound();

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  Response
     * @since   2.0.0
     */
    public function methodNotAllowed($requestMethod, array $allowedMethods);

    /**
     * creates a 406 Not Acceptable message
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @return  Response
     * @since   2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = []);

    /**
     * creates a 500 Internal Server Error message
     *
     * @param   string  $errorMessage
     * @return  Response
     * @since   2.0.0
     */
    public function internalServerError($errorMessage);

    /**
     * creates a 505 HTTP Version Not Supported message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function httpVersionNotSupported();

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send();

    /**
     * sends head only
     *
     * @return  Response
     * @since   2.0.0
     */
    public function sendHead();
}
