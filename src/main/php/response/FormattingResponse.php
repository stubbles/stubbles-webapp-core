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
use stubbles\webapp\response\format\Formatter;
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
     * @type  \stubbles\webapp\response\Response
     */
    private $response;
    /**
     * formatter to be used
     *
     * @type  \stubbles\webapp\response\format\Formatter
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
     * @param  \stubbles\webapp\response\Response          $response
     * @param  \stubbles\webapp\response\format\Formatter  $formatter
     * @param  string                                      $mimeType
     */
    public function __construct(Response $response, Formatter $formatter, $mimeType)
    {
        $this->response  = $response;
        $this->formatter = $formatter;
        $this->mimeType  = $mimeType;
    }

    /**
     * clears the response
     *
     * @return  \stubbles\webapp\response\Response
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
     * @param   int     $statusCode
     * @param   string  $reasonPhrase  optional
     * @return  \stubbles\webapp\response\Response
     */
    public function setStatusCode($statusCode, $reasonPhrase = null)
    {
        $this->response->setStatusCode($statusCode, $reasonPhrase);
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
        return $this->response->statusCode();
    }

    /**
     * provide direct access to set a status code
     *
     * @return  \stubbles\webapp\response\Status
     * @since   5.1.0
     */
    public function status()
    {
        return $this->response->status();
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
        $this->response->addHeader($name, $value);
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
        return $this->response->headers();
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
        return $this->response->containsHeader($name, $value);
    }

    /**
     * add a cookie to the response
     *
     * @param   \stubbles\webapp\response\Cookie  $cookie  the cookie to set
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
     */
    public function removeCookie($name)
    {
        $this->response->removeCookie($name);
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
        return $this->response->containsCookie($name, $value);
    }

    /**
     * write body into the response
     *
     * @param   string  $body
     * @return  \stubbles\webapp\response\Response
     */
    public function write($body)
    {
        $this->response->write($this->formatter->format($body, $this->headers()));
        return $this;
    }

    /**
     * returns response body
     *
     * @return  string
     * @since   4.0.0
     */
    public function body()
    {
        return $this->response->body();
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
     * @param   string|\stubbles\peer\http\HttpUri  $uri         http uri to redirect to
     * @param   int                                 $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  \stubbles\webapp\response\Response
     */
    public function redirect($uri, $statusCode = 302)
    {
        $this->response->redirect($uri, $statusCode);
        return $this;
    }

    /**
     * creates a 403 Forbidden message
     *
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
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
     * @return  \stubbles\webapp\response\Response
     * @since   2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = [])
    {
        $this->response->notAcceptable($supportedMimeTypes);
        return $this;
    }

    /**
     * creates a 500 Internal Server Error message
     *
     * @param   string  $errorMessage
     * @return  \stubbles\webapp\response\Response
     */
    public function internalServerError($errorMessage)
    {
        $this->response->internalServerError($this->formatter->formatInternalServerError($errorMessage));
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
        $this->response->httpVersionNotSupported();
        return $this;
    }

    /**
     * send the response out
     *
     * @return  \stubbles\webapp\response\Response
     */
    public function send()
    {
        $this->response->addHeader('Content-type', $this->mimeType)
                       ->send();
        return $this;
    }
}
