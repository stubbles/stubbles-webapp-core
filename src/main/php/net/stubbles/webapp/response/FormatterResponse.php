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
use net\stubbles\webapp\response\format\Formatter;
/**
 * Response which is able to format the response body if it is not a string.
 *
 * @since  2.0.0
 */
class FormatterResponse extends BaseObject implements FormattingResponse
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
     * mime type for response
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
    public function __construct(Response $response, Formatter $formatter, $mimeType)
    {
        $this->response  = $response;
        $this->formatter = $formatter;
        $this->mimeType  = $mimeType;
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
     */
    public function merge(Response $other)
    {
        $this->response->merge($other);
        return $this;
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
     * returns the http version
     *
     * @return  string
     */
    public function getVersion()
    {
        return $this->response->getVersion();
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
        $this->response->setStatusCode($statusCode);
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
        return $this->response->getStatusCode();
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
     * returns the list of headers
     *
     * @return  array
     */
    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    /**
     * checks if header with given name is set
     *
     * @param   string  $name
     * @return  bool
     */
    public function hasHeader($name)
    {
        return $this->response->hasHeader($name);
    }

    /**
     * returns header with given name
     *
     * If header with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  string
     */
    public function getHeader($name)
    {
        return $this->response->getHeader($name);
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
     * @return  Response
     */
    public function removeCookie($name)
    {
        $this->response->removeCookie($name);
        return $this;
    }

    /**
     * returns the list of cookies
     *
     * @return  Cookie[]
     */
    public function getCookies()
    {
        return $this->response->getCookies();
    }

    /**
     * checks if cookie with given name is set
     *
     * @param   string  $name
     * @return  bool
     */
    public function hasCookie($name)
    {
        return $this->response->hasCookie($name);
    }

    /**
     * returns cookie with given name
     *
     * If cookie with given name does not exist return value is null.
     *
     * @param   string  $name
     * @return  Cookie
     */
    public function getCookie($name)
    {
        return $this->response->getCookie($name);
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
     * writes a Forbidden message into response body
     *
     * @return  Response
     */
    public function writeForbiddenError()
    {
        $this->write($this->formatter->formatForbiddenError());
        return $this;
    }

    /**
     * writes a Not Found message into response body
     *
     * @return  Response
     */
    public function writeNotFoundError()
    {
        $this->write($this->formatter->formatNotFoundError());
        return $this;
    }

    /**
     * writes a Method Not Allowed message into response body
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  Response
     */
    public function writeMethodNotAllowedError($requestMethod, array $allowedMethods)
    {
        $this->addHeader('Allow', join(', ', $allowedMethods))
             ->write($this->formatter->formatMethodNotAllowedError($requestMethod, $allowedMethods));
        return $this;
    }

    /**
     * writes an Internal Server Error message into response body
     *
     * @param   string  $errorMessage
     * @return  Response
     */
    public function writeInternalServerError($errorMessage)
    {
        $this->write($this->formatter->formatInternalServerError($errorMessage));
        return $this;
    }

    /**
     * returns the data written so far
     *
     * @return  string
     */
    public function getBody()
    {
        return $this->response->getBody();
    }

    /**
     * replaces the data written so far with the new data
     *
     * @param   string  $data
     * @return  Response
     */
    public function replaceBody($body)
    {
        $this->response->clearBody();
        $this->write($body);
        return $this;
    }

    /**
     * removes data completely
     *
     * @return  Response
     */
    public function clearBody()
    {
        $this->response->clearBody();
        return $this;
    }

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string  $url           url to redirect to
     * @param   int     $statusCode    HTTP status code to redirect with (301, 302, ...)
     * @return  Response
     */
    public function redirect($url, $statusCode = 302)
    {
        $this->response->redirect($url, $statusCode);
        return $this;
    }

    /**
     * send the response out
     *
     * @return  Response
     */
    public function send()
    {
        if (null !== $this->mimeType) {
            $this->response->addHeader('Content-type', $this->mimeType)
                           ->addHeader('Content-Length', strlen($this->response->getBody()));
        }

        $this->response->send();
        return $this;
    }
}
?>