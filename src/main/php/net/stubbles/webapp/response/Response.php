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
     * merges other response into this instance
     *
     * All values of the current instance will be overwritten by the other
     * instance. However, merging does not change the http version of this
     * response instance. Cookies and headers which are present in this instance
     * but not in the other instance will be kept.
     *
     * @param   Response  $other
     * @return  Response
     * @since   1.7.0
     */
    public function merge(Response $other);

    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear();

    /**
     * returns the http version
     *
     * @return  string
     */
    public function getVersion();

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
     * returns status code to be send
     *
     * If return value is <null> the default one will be send.
     *
     * @return  int
     */
    public function getStatusCode();

    /**
     * add a header to the response
     *
     * @param   string  $name   the name of the header
     * @param   string  $value  the value of the header
     * @return  Response
     */
    public function addHeader($name, $value);

    /**
     * returns the list of headers
     *
     * @return  array
     */
    public function getHeaders();

    /**
     * checks if header with given name is set
     *
     * @param   string  $name
     * @return  bool
     * @since   1.5.0
     */
    public function hasHeader($name);

    /**
     * returns header with given name
     *
     * If header with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  string
     * @since   1.5.0
     */
    public function getHeader($name);

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
     * returns the list of cookies
     *
     * @return  Cookie[]
     */
    public function getCookies();

    /**
     * checks if cookie with given name is set
     *
     * @param   string  $name
     * @return  bool
     * @since   1.5.0
     */
    public function hasCookie($name);

    /**
     * returns cookie with given name
     *
     * If cookie with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  Cookie
     * @since   1.5.0
     */
    public function getCookie($name);

    /**
     * write body into the response
     *
     * @param   string  $body
     * @return  Response
     */
    public function write($body);

    /**
     * returns the data written so far
     *
     * @return  string
     * @since   1.7.0
     */
    public function getBody();

    /**
     * replaces the data written so far with the new data
     *
     * @param   string  $data
     * @return  Response
     * @since   1.7.0
     */
    public function replaceBody($body);

    /**
     * removes data completely
     *
     * @return  Response
     * @since   1.7.0
     */
    public function clearBody();

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
     * send the response out
     *
     * @return  Response
     */
    public function send();
}
?>