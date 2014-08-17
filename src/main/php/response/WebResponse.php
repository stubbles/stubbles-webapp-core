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
use stubbles\input\web\WebRequest;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpVersion;
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
     * @type  \stubbles\peer\http\HttpVersion
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
     * @type  \stubbles\webapp\response\Headers
     */
    private $headers;
    /**
     * list of cookies for this response
     *
     * @type  \stubbles\webapp\response\Cookie[]
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
     * original request method
     *
     * @type  string
     */
    private $request;

    /**
     * constructor
     *
     * In case the request contains an invalid HTTP protocol version or the HTTP
     * protocol major version is not 1 the response automatically sets itself
     * to 500 Method Not Supported.
     *
     * @param  \stubbles\input\web\WebRequest  $request  http request for which this is the response
     * @param  string                          $sapi     optional  current php sapi, defaults to value of PHP_SAPI constant
     */
    public function __construct(WebRequest $request, $sapi = PHP_SAPI)
    {
        $this->request = $request;
        $this->sapi    = $sapi;
        $this->headers = new Headers();
        $this->version = $request->protocolVersion();
        if (null === $this->version || $this->version->major() != 1) {
            $this->version = HttpVersion::fromString(HttpVersion::HTTP_1_1);
            $this->httpVersionNotSupported();
        } else {
            $this->setStatusCode(200);
        }
    }

    /**
     * clears the response
     *
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
     */
    public function setStatusCode($statusCode)
    {
        $this->status = $statusCode . ' ' . Http::reasonPhraseFor($statusCode);
        return $this;
    }

    /**
     * returns status code of response
     *
     * @return  int
     * @since   4.0.0
     */
    public function statusCode()
    {
        return (int) explode(' ', $this->status)[0];
    }

    /**
     * add a header to the response
     *
     * @param   string  $name   the name of the header
     * @param   string  $value  the value of the header
     * @return  \stubbles\webapp\response\Response
     */
    public function addHeader($name, $value)
    {
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * returns list of headers
     *
     * @return  \stubbles\webapp\response\Headers
     * @since   4.0.0
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * check if response contains a certain header
     *
     * @param   string  $name   name of header to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     * @since   4.0.0
     */
    public function containsHeader($name, $value = null)
    {
        if ($this->headers->contain($name)) {
            if (null !== $value) {
                return $value === $this->headers[$name];
            }

            return true;
        }

        return false;
    }

    /**
     * add a cookie to the response
     *
     * @param   \stubbles\webapp\response\Cookie  $cookie  the cookie to set
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * checks if response contains a certain cookie
     *
     * @param   string  $name   name of cookie to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     * @since   4.0.0
     */
    public function containsCookie($name, $value = null)
    {
        if (isset($this->cookies[$name])) {
            if (null !== $value) {
                return $this->cookies[$name]->value() === $value;
            }

            return true;
        }

        return false;
    }

    /**
     * write data into the response
     *
     * @param   string  $body
     * @return  \stubbles\webapp\response\Response
     */
    public function write($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * returns response body
     *
     * @return  string
     */
    public function body()
    {
        return $this->body;
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
     * @param   string|\stubbles\peer\http\HttpUri  $uri         http uri to redirect to
     * @param   int                                 $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
     */
    public function send()
    {
        $this->sendHead();
        if ($this->requestAllowsBody() && null != $this->body) {
            $this->sendBody($this->body);
        }

        return $this;
    }

    /**
     * checks of the request allows a body in the response
     *
     * @return  bool
     * @since   4.0.0
     */
    protected function requestAllowsBody()
    {
        return Http::HEAD !== $this->request->method();
    }

    /**
     * sends head only
     *
     * @return  \stubbles\webapp\response\Response
     */
    private function sendHead()
    {
        if ('cgi' === $this->sapi) {
            $this->header('Status: ' . $this->status);
        } else {
            $this->header($this->version . ' ' . $this->status);
        }

        foreach ($this->headers as $name => $value) {
            $this->header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            $cookie->send();
        }

        if (null != $this->body && !$this->headers->contain('Content-Length')) {
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
