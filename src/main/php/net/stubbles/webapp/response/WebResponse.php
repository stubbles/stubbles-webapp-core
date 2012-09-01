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
     * creates a 403 Forbidden message
     *
     * @return  Response
     * @since   2.0.0
     */
    public function forbidden()
    {
        $this->setStatusCode(403);
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
        $this->setStatusCode(405)
             ->addHeader('Allow', join(', ', $allowedMethods));
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
        $this->setStatusCode(406);
        if (count($supportedMimeTypes) > 0) {
            $this->addHeader('X-Acceptable', join(', ', $supportedMimeTypes));
        }

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
            $this->header('Content-Length: ' . strlen($this->body));
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