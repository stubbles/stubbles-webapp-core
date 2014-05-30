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
use net\stubbles\webapp\response\format\Formatter;
/**
 * Response which is able to format the response body if it is not a string.
 *
 * @since  2.0.0
 */
class FormattingResponse implements Response
{
    /**
     * decorated response
     *
     * @type  Response
     */
    private $response;
    /**
     * formatter to be used
     *
     * @type  Formatter
     */
    private $formatter;
    /**
     * actual mime type for the response
     *
     * @type  string
     */
    private $mimeType;

    /**
     * constructor
     *
     * @param  Response   $response
     * @param  Formatter  $formatter
     * @param  string     $mimeType
     */
    public function __construct(Response $response, Formatter $formatter, $mimeType = null)
    {
        $this->response  = $response;
        $this->formatter = $formatter;
        $this->mimeType  = $mimeType;
    }

    /**
     * clears the response
     *
     * @return  Response
     */
    public function clear()
    {
        $this->response->clear();
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
        $this->response->setStatusCode($statusCode);
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
        $this->response->addHeader($name, $value);
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
        return $this->response->headers();
    }

    /**
     * add a cookie to the response
     *
     * @param   Cookie  $cookie  the cookie to set
     * @return  Response
     */
    public function addCookie(Cookie $cookie)
    {
        $this->response->addCookie($cookie);
        return $this;
    }

    /**
     * removes cookie with given name
     *
     * @param   string  $name
     * @return  Response
     */
    public function removeCookie($name)
    {
        $this->response->removeCookie($name);
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
        $result = ((is_string($body)) ? ($body): ($this->formatter->format($body)));
        $this->response->write($result);
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
        return $this->response->isFixed();
    }

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string|HttpUri  $uri         http uri to redirect to
     * @param   int             $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  Response
     */
    public function redirect($uri, $statusCode = 302)
    {
        $this->response->redirect($uri, $statusCode);
        return $this;
    }

    /**
     * creates a 403 Forbidden message
     *
     * @return  Response
     */
    public function forbidden()
    {
        $this->response->forbidden()
                       ->write($this->formatter->formatForbiddenError());
        return $this;
    }

    /**
     * creates a 404 Not Found message
     *
     * @return  Response
     */
    public function notFound()
    {
        $this->response->notFound()
                       ->write($this->formatter->formatNotFoundError());
        return $this;
    }

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  Response
     */
    public function methodNotAllowed($requestMethod, array $allowedMethods)
    {
        $this->response->methodNotAllowed($requestMethod, $allowedMethods)
                       ->write($this->formatter->formatMethodNotAllowedError($requestMethod, $allowedMethods));
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
     * creates a 500 Internal Server Error message
     *
     * @param   string  $errorMessage
     * @return  Response
     */
    public function internalServerError($errorMessage)
    {
        $this->response->internalServerError($this->formatter->formatInternalServerError($errorMessage));
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
        $this->response->httpVersionNotSupported();
        return $this;
    }

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send()
    {
        $this->addContentType();
        $this->response->send();
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
        $this->addContentType();
        $this->response->sendHead();
        return $this;
    }

    /**
     * adds content type header
     */
    private function addContentType()
    {
        if (null !== $this->mimeType) {
            $this->response->addHeader('Content-type', $this->mimeType);
        }
    }
}
