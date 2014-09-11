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
/**
 * Represents the response status line,
 *
 * @since  5.1.0
 */
class Status
{
    /**
     * status code to be send
     *
     * @type  int
     */
    private $code         = 200;
    /**
     * reason phrase for status code
     *
     * @type  string
     */
    private $reasonPhrase = null;
    /**
     * switch whether response is fixed or not
     *
     * @type  bool
     */
    private $fixed        = false;
    /**
     * list of headers for this response
     *
     * @type  \stubbles\webapp\response\Headers
     */
    private $headers;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\response\Headers  $headers
     */
    public function __construct(Headers $headers)
    {
        $this->headers = $headers;
    }

    /**
     * sets the status code
     *
     * If reason phrase is null it will use the default reason phrase for given
     * status code.
     *
     * @param   int     $code
     * @param   string  $reasonPhrase  optional
     * @return  \stubbles\webapp\response\Status
     */
    public function setCode($code, $reasonPhrase = null)
    {
        $this->code         = $code;
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri         http uri to redirect to
     * @param   int                                 $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  \stubbles\webapp\response\Status
     */
    public function redirect($uri, $statusCode = 302)
    {
        $this->headers->location($uri);
        $this->setCode($statusCode);
        return $this;
    }

    /**
     * sets status to 403 Forbidden
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function forbidden()
    {
        $this->setCode(403);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 404 Not Found
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function notFound()
    {
        $this->setCode(404);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 405 Method Not Allowed
     *
     * @param   string    $requestMethod
     * @param   string[]  $allowedMethods
     * @return  \stubbles\webapp\response\Status
     */
    public function methodNotAllowed(array $allowedMethods)
    {
        $this->setCode(405);
        $this->headers->allow($allowedMethods);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 406 Not Acceptable
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @return  \stubbles\webapp\response\Status
     */
    public function notAcceptable(array $supportedMimeTypes = [])
    {
        $this->setCode(406);
        $this->headers->acceptable($supportedMimeTypes);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 500 Internal Server Error
     *
     * @return  \stubbles\webapp\response\Status
     * @since   2.0.0
     */
    public function internalServerError()
    {
        $this->setCode(500);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 505 HTTP Version Not Supported
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function httpVersionNotSupported()
    {
        $this->setCode(505);
        $this->fixed = true;
        return $this;
    }

    /**
     * returns current status code
     *
     * @return  int
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * a status is fixed when a final status has been set
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
     */
    public function isFixed()
    {
        return $this->fixed;
    }

    /**
     * returns status line
     *
     * @param   string  $httpVersion
     * @param   string  $sapi         optional
     * @return  string
     */
    public function line($httpVersion, $sapi = PHP_SAPI)
    {
        if ('cgi' === $sapi) {
            return 'Status: ' . $this->code . ' ' . $this->reasonPhrase();
        }

        return $httpVersion . ' ' . $this->code . ' ' . $this->reasonPhrase();
    }

    /**
     * returns reason phrase for the status
     *
     * @return  string
     */
    private function reasonPhrase()
    {
        if (null !== $this->reasonPhrase) {
            return $this->reasonPhrase;
        }

        return Http::reasonPhraseFor($this->code);
    }
}
