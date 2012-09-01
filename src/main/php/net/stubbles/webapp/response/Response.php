<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
use net\stubbles\lang\Object;
/**
 * Interface for a response to a request.
 *
 * The response collects all data that should be send to the source
 * that initiated the request.
 */
interface Response extends Object
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
     * 200 Found should be send.
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
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie);

    /**
     * removes cookie with given name
     *
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
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string  $url           url to redirect to
     * @param   int     $statusCode    HTTP status code to redirect with (301, 302, ...)
     * @param   string  $reasonPhrase  HTTP status code reason phrase
     * @return  Response
     * @since   1.3.0
     */
    public function redirect($url, $statusCode = 302);

    /**
     * writes a Forbidden message into response body
     *
     * @return  Response
     * @since   2.0.0
     */
    public function forbidden();

    /**
     * writes a Not Found message into response body
     *
     * @return  Response
     * @since   2.0.0
     */
    public function notFound();

    /**
     * writes a Method Not Allowed message into response body
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  Response
     * @since   2.0.0
     */
    public function methodNotAllowed($requestMethod, array $allowedMethods);

    /**
     * writes an Internal Server Error message into response body
     *
     * @param   string  $errorMessage
     * @return  Response
     * @since   2.0.0
     */
    public function internalServerError($errorMessage);

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send();
}
?>