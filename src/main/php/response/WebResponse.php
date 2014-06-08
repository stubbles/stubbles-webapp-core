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
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpUri;
/**
 * Base class for a response to a request.
 *
 * This class can be used for responses in web environments. It
 * collects all data of the response and is able to send it back
 * to the source that initiated the request.
 */
class WebResponse implements Response
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
    private $status   = 200;
    /**
     * list of headers for this response
     *
     * @type  Headers
     */
    private $headers;
    /**
     * list of cookies for this response
     *
     * @type  Cookie[]
     */
    private $cookies  = [];
    /**
     * data to send as body of response
     *
     * @type  string
     */
    private $body;
    /**
     * switch whether response is fixed or not
     *
     * @type  bool
     */
    private $fixed    = false;

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
        $this->headers = new Headers();
        $this->setStatusCode(200);
    }

    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear()
    {
        $this->setStatusCode(200);
        $this->headers = new Headers();
        $this->cookies = [];
        $this->body    = null;
        $this->fixed   = false;
        return $this;
    }

    /**
     * sets the status code to be send
     *
     * This needs only to be done if another status code then the default one
     * 200 OK should be send.
     *
     * @param   int  $statusCode
     * @return  Response
     */
    public function setStatusCode($statusCode)
    {
        $this->status = $statusCode . ' ' . Http::reasonPhraseFor($statusCode);
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
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * returns list of headers
     *
     * @return  Headers
     * @since   4.0.0
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->name()] = $cookie;
        return $this;
    }

    /**
     * removes cookie with given name
     *
     * @param   string  $name
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
     * write data into the response
     *
     * @param   string  $body
     * @return  Response
     */
    public function write($body)
    {
        $this->body = $body;
        return $this;
    }

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
    public function isFixed()
    {
        return $this->fixed;
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
        $this->headers->location($uri);
        $this->setStatusCode($statusCode);
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
        $this->setStatusCode(403);
        $this->fixed = true;
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
        $this->setStatusCode(404);
        $this->fixed = true;
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
        $this->setStatusCode(405);
        $this->headers->allow($allowedMethods);
        $this->fixed = true;
        return $this;
    }

    /**
     * creates a 406 Not Acceptable message
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @return  Response
     * @since   2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = [])
    {
        $this->setStatusCode(406);
        $this->headers->acceptable($supportedMimeTypes);
        $this->fixed = true;
        return $this;
    }

    /**
     * creates a 500 Internal Server Error message
     *
     * @param   string  $errorMessage
     * @return  Response
     * @since   2.0.0
     */
    public function internalServerError($errorMessage)
    {
        $this->setStatusCode(500)
             ->write($errorMessage);
        $this->fixed = true;
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
        $this->setStatusCode(505)
             ->write('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1');
        $this->fixed = true;
        return $this;
    }

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send()
    {
        $this->sendHead();
        if (null != $this->body) {
            $this->sendBody($this->body);
        }

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
        if ('cgi' === $this->sapi) {
            $this->header('Status: ' . $this->status);
        } else {
            $this->header('HTTP/' . $this->version . ' ' . $this->status);
        }

        foreach ($this->headers as $name => $value) {
            $this->header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            $cookie->send();
        }

        if (null != $this->body) {
            $this->header('Content-Length: ' . strlen($this->body));
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
