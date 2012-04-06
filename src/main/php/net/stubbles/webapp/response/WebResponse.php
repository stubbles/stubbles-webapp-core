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
use net\stubbles\lang\BaseObject;
use net\stubbles\peer\http\Http;
use net\stubbles\peer\http\HttpUri;
/**
 * Base class for a response to a request.
 *
 * This class can be used for responses in web environments. It
 * collects all data of the response and is able to send it back
 * to the source that initiated the request.
 */
class WebResponse extends BaseObject implements Response
{
    /**
     * current php sapi
     *
     * @type  string
     */
    private $sapi;
    /**
     * http version to be used
     *
     * @type  string
     */
    private $version;
    /**
     * status code to be send
     *
     * @type  int
     */
    private $statusCode   = 200;
    /**
     * status message to be send
     *
     * @type  string
     */
    private $reasonPhrase = 'OK';
    /**
     * list of headers for this response
     *
     * @type  array
     */
    private $headers      = array();
    /**
     * list of cookies for this response
     *
     * @type  Cookie[]
     */
    private $cookies      = array();
    /**
     * data to send as body of response
     *
     * @type  string
     */
    private $body;

    /**
     * constructor
     *
     * @param  string  $version  http version      should be a string like '1.0' or '1.1'
     * @param  string  $sapi     current php sapi
     */
    public function __construct($version = '1.1', $sapi = PHP_SAPI)
    {
        $this->version = $version;
        $this->sapi    = $sapi;
    }

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
    public function merge(Response $other)
    {
        $this->setStatusCode($other->getStatusCode());
        foreach ($other->getHeaders() as $name => $value) {
            $this->addHeader($name, $value);
        }

        foreach ($other->getCookies() as $cookie) {
            $this->addCookie($cookie);
        }

        $this->clearBody()->write($other->getBody());
        return $this;
    }

    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear()
    {
        $this->setStatusCode(200);
        $this->headers = array();
        $this->cookies = array();
        $this->body    = null;
        return $this;
    }

    /**
     * returns the http version
     *
     * @return  string
     */
    public function getVersion()
    {
        return $this->version;
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
        $this->reasonPhrase = Http::getReasonPhrase($statusCode);
        $this->statusCode   = $statusCode;
        return $this;
    }

    /**
     * returns status code to be send
     *
     * If return value is <null> the default one will be send.
     *
     * @return  int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
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
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * returns the list of headers
     *
     * @return  array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * checks if header with given name is set
     *
     * @param   string  $name
     * @return  bool
     * @since   1.5.0
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * returns header with given name
     *
     * If header with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  string
     * @since   1.5.0
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name) === true) {
            return $this->headers[$name];
        }

        return null;
    }

    /**
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getName()] = $cookie;
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
        $this->addCookie(Cookie::create($name, 'remove')
                               ->expiringAt(time() - 86400)
        );
        return $this;
    }

    /**
     * returns the list of cookies
     *
     * @return  Cookie[]
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * checks if cookie with given name is set
     *
     * @param   string  $name
     * @return  bool
     * @since   1.5.0
     */
    public function hasCookie($name)
    {
        return isset($this->cookies[$name]);
    }

    /**
     * returns cookie with given name
     *
     * If cookie with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  Cookie
     * @since   1.5.0
     */
    public function getCookie($name)
    {
        if ($this->hasCookie($name) === true) {
            return $this->cookies[$name];
        }

        return null;
    }

    /**
     * write data into the response
     *
     * @param   string  $body
     * @return  Response
     */
    public function write($body)
    {
        $this->body .= $body;
        return $this;
    }

    /**
     * returns the data written so far
     *
     * @return  string
     * @since   1.7.0
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * replaces the data written so far with the new data
     *
     * @param   string  $body
     * @return  Response
     * @since   1.7.0
     */
    public function replaceBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * removes data completely
     *
     * @return  Response
     * @since   1.7.0
     */
    public function clearBody()
    {
        $this->body = null;
        return $this;
    }

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
    public function redirect($uri, $statusCode = 302)
    {
        $this->addHeader('Location', (($uri instanceof HttpUri) ? ($uri->asStringWithNonDefaultPort()) : ($uri)));
        $this->setStatusCode($statusCode);
        return $this;
    }

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send()
    {
        if ('cgi' === $this->sapi) {
            $this->header('Status: ' . $this->statusCode . ' ' . $this->reasonPhrase);
        } else {
            $this->header('HTTP/' . $this->version . ' ' . $this->statusCode . ' ' . $this->reasonPhrase);
        }

        foreach ($this->headers as $name => $value) {
            $this->header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            $cookie->send();
        }

        if (null != $this->body) {
            $this->sendBody($this->body);
        }

        return $this;
    }

    /**
     * helper method to send the header
     *
     * @param  string  $header
     */
    protected function header($header)
    {
        header($header);
    }

    /**
     * helper method to send the body
     *
     * @param  string  $body
     */
    protected function sendBody($body)
    {
        echo $body;
        flush();
    }
}
?>