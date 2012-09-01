<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace org\stubbles\webapp\response;
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\response\Cookie;
use net\stubbles\webapp\response\Response;
/**
 * Dummy response implementation which does nothing.
 */
class DummyResponse extends BaseObject implements Response
{
    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear()
    {
        return $this;
    }

    /**
     * sets the status code to be send
     *
     * This needs only to be done if another status code then the default one
     * 200 Found should be send.
     *
     * @param   int  $statusCode
     * @return  Response
     */
    public function setStatusCode($statusCode)
    {
        return $this;
    }

    /**
     * add a header to the response
     *
     * @param   string  $name   the name of the header
     * @param   string  $value  the value of the header
     * @return  Response
     */
    public function addHeader($name, $value)
    {
        return $this;
    }

    /**
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie)
    {
        return $this;
    }

    /**
     * removes cookie with given name
     *
     * @return  Response
     * @since   2.0.0
     */
    public function removeCookie($name)
    {
        return $this;
    }

    /**
     * write body into the response
     *
     * @param   string  $body
     * @return  Response
     */
    public function write($body)
    {
        return $this;
    }

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
    public function redirect($url, $statusCode = 302)
    {
        return $this;
    }

    /**
     * creates a 403 Forbidden message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function forbidden()
    {
        return $this;
    }

    /**
     * creates a 404 Not Found message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function notFound()
    {
        return $this;
    }

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  Response
     * @since   2.0.0
     */
    public function methodNotAllowed($requestMethod, array $allowedMethods)
    {
        return $this;
    }

    /**
     * creates a 406 Not Acceptable message
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @return  Response
     * @since   2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = array())
    {
        $this->response->notAcceptable($supportedMimeTypes);
        return $this;
    }

    /**
     * creates a 500 Internal Server Error
     *
     * @param   string  $errorMessage
     * @return  Response
     * @since   2.0.0
     */
    public function internalServerError($errorMessage)
    {
        return $this;
    }

    /**
     * creates a 505 HTTP Version Not Supported message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function httpVersionNotSupported()
    {
        return $this;
    }

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send()
    {
        return $this;
    }

    /**
     * sends head only
     *
     * @return  Response
     * @since   2.0.0
     */
    public function sendHead()
    {
        return $this;
    }
}
?>