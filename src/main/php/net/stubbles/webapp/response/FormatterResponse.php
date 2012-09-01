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
     * writes a Forbidden message into response body
     *
     * @return  FormattingResponse
     */
    public function forbidden()
    {
        $this->response->setStatusCode(403)
                       ->write($this->formatter->formatForbiddenError());
        return $this;
    }

    /**
     * writes a Not Found message into response body
     *
     * @return  FormattingResponse
     */
    public function notFound()
    {
        $this->response->setStatusCode(404)
                       ->write($this->formatter->formatNotFoundError());
        return $this;
    }

    /**
     * writes a Method Not Allowed message into response body
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  FormattingResponse
     */
    public function methodNotAllowed($requestMethod, array $allowedMethods)
    {
        $this->response->setStatusCode(405)
                       ->addHeader('Allow', join(', ', $allowedMethods))
                       ->write($this->formatter->formatMethodNotAllowedError($requestMethod, $allowedMethods));
        return $this;
    }

    /**
     * writes an Internal Server Error message into response body
     *
     * @param   string  $errorMessage
     * @return  FormattingResponse
     */
    public function internalServerError($errorMessage)
    {
        $this->response->setStatusCode(500)
                       ->write($this->formatter->formatInternalServerError($errorMessage));
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
            $this->response->addHeader('Content-type', $this->mimeType);
        }

        $this->response->send();
        return $this;
    }
}
?>