<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use Exception;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpUri;
use stubbles\peer\http\HttpVersion;
use stubbles\streams\OutputStream;
use stubbles\streams\StandardOutputStream;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\response\mimetypes\MimeType;
use stubbles\webapp\response\mimetypes\PassThrough;

/**
 * Base class for a response to a request.
 *
 * This class can be used for responses in web environments. It
 * collects all data of the response and is able to send it back
 * to the source that initiated the request.
 */
class WebResponse implements Response
{
    private HttpVersion $version;
    private Status $status;
    private Headers $headers;
    /** @var  Cookie[] */
    private array $cookies = [];
    /** data to send as body of response */
    private mixed $resource = null;
    private MimeType $mimeType;

    /**
     * In case the request contains an invalid HTTP protocol version or the HTTP
     * protocol major version is not 1 the response automatically sets itself
     * to 500 Method Not Supported.
     *
     * @param  Request    $request   http request for which this is the response
     * @param  MimeType  $mimeType  mime type for response body
     * @param  string    $sapi      current php sapi, defaults to value of PHP_SAPI constant
     */
    public function __construct(
        private Request $request,
        MimeType $mimeType = null,
        private string $sapi = PHP_SAPI)
    {
        $this->mimeType = null !== $mimeType ? $mimeType : new PassThrough();
        $this->headers  = new Headers();
        $this->status   = new Status($this->headers);
        $version  = $request->protocolVersion();
        if (null === $version || $version->major() != 1) {
            $this->version = HttpVersion::fromString(HttpVersion::HTTP_1_1);
            $this->httpVersionNotSupported();
        } else {
            $this->version = $version;
        }
    }

    /**
     * adjusts mime type of response to given mime type
     */
    public function adjustMimeType(MimeType $mimeType): Response
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * returns mime type for response body
     */
    public function mimeType(): MimeType
    {
        return $this->mimeType;
    }

    /**
     * sets the status code to be send
     *
     * This needs only to be done if another status code then the default one
     * 200 OK should be send.
     */
    public function setStatusCode(int $statusCode, string $reasonPhrase = null): Response
    {
        $this->status->setCode($statusCode, $reasonPhrase);
        return $this;
    }

    /**
     * returns status code of response
     *
     * @since  4.0.0
     */
    public function statusCode(): int
    {
        return $this->status->code();
    }

    /**
     * provide direct access to set a status code
     *
     * @since  5.1.0
     */
    public function status(): Status
    {
        return $this->status;
    }

    /**
     * add a header to the response
     */
    public function addHeader(string $name, string $value): Response
    {
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * returns list of headers
     *
     * @since  4.0.0
     */
    public function headers(): Headers
    {
        return $this->headers;
    }

    /**
     * check if response contains a certain header
     *
     * @param  string  $name   name of header to check
     * @param  string  $value  optional  if given the value is checked as well
     * @since  4.0.0
     */
    public function containsHeader(string $name, string $value = null): bool
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
     */
    public function addCookie(Cookie $cookie): Response
    {
        $this->cookies[$cookie->name()] = $cookie;
        return $this;
    }

    /**
     * removes cookie with given name
     *
     * @since  2.0.0
     */
    public function removeCookie(string $name): Response
    {
        $this->addCookie(
            Cookie::create($name, 'remove')->expiringAt(time() - 86400)
        );
        return $this;
    }

    /**
     * checks if response contains a certain cookie
     *
     * @param  string  $name   name of cookie to check
     * @param  string  $value  optional  if given the value is checked as well
     * @since  4.0.0
     */
    public function containsCookie(string $name, string $value = null): bool
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
     */
    public function write(mixed $resource): Response
    {
        $this->resource = $resource;
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
     * @since   3.1.0
     */
    public function isFixed(): bool
    {
        return $this->status->isFixed();
    }

    /**
     * creates a Location header which causes a redirect when the response is send
     *
     * Status code is optional, default is 302.
     *
     * @since   1.3.0
     */
    public function redirect(string|HttpUri $uri, int $statusCode = 302): void
    {
        $this->status->redirect($uri, $statusCode);
    }

    /**
     * creates a 401 Unauthorized message including a WWW-Authenticate header with given challenge
     *
     * @param  string[]  $challenges
     * @since  8.0.0
     */
    public function unauthorized(array $challenges): Error
    {
        $this->status->unauthorized($challenges);
        return Error::unauthorized();
    }

    /**
     * creates a 403 Forbidden message
     *
     * @since   2.0.0
     */
    public function forbidden(): Error
    {
        $this->status->forbidden();
        return Error::forbidden();
    }

    /**
     * creates a 404 Not Found message
     *
     * @since  2.0.0
     */
    public function notFound(): Error
    {
        $this->status->notFound();
        return Error::notFound();
    }

    /**
     * creates a 405 Method Not Allowed message
     *
     * @param  string[]  $allowedMethods
     * @since  2.0.0
     */
    public function methodNotAllowed(string $requestMethod, array $allowedMethods): Error
    {
        $this->status->methodNotAllowed($allowedMethods);
        return Error::methodNotAllowed($requestMethod, $allowedMethods);
    }

    /**
     * creates a 406 Not Acceptable message
     *
     * @param  string[]  $supportedMimeTypes  list of supported mime types
     * @since  2.0.0
     */
    public function notAcceptable(array $supportedMimeTypes = []): void
    {
        $this->status->notAcceptable($supportedMimeTypes);
    }

    /**
     * creates a 500 Internal Server Error message
     *
     * @since  2.0.0
     */
    public function internalServerError(string|Exception $error): Error
    {
        $this->status->internalServerError();
        return Error::internalServerError($error);
    }

    /**
     * creates a 505 HTTP Version Not Supported message
     *
     * @since  2.0.0
     */
    public function httpVersionNotSupported(): void
    {
        $this->status->httpVersionNotSupported();
        $this->resource = new Error(
            'Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'
        );
    }

    /**
     * sends response
     *
     * In case no output stream is passed it will create a
     * stubbles\streams\StandardOutputStream where the response body will be
     * written to.
     * The output stream is returned. In case no output stream was passed and
     * the request doesn't allow a response body or no resource for the response
     * body was set the return value is null because no standard stream will be
     * created in such a case.
     */
    public function send(OutputStream $out = null): ?OutputStream
    {
        $this->sendHead();
        if (!$this->requestAllowsBody() || null == $this->resource) {
            return $out;
        }

        $out = (null === $out ? new StandardOutputStream() : $out);
        return $this->mimeType->serialize($this->resource, $out);
    }

    /**
     * checks of the request allows a body in the response
     *
     * @since  4.0.0
     */
    protected function requestAllowsBody(): bool
    {
        return Http::HEAD !== $this->request->method();
    }

    /**
     * sends head only
     */
    private function sendHead(): Response
    {
        $this->header($this->status->line((string) $this->version, $this->sapi));
        foreach ($this->headers as $name => $value) {
            $this->header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            $cookie->send();
        }

        $this->header('Content-type: ' . $this->mimeType);
        if (!$this->headers->contain('X-Request-ID')) {
            $this->header('X-Request-ID: ' . $this->request->id());
        }

        return $this;
    }

    /**
     * helper method to send the header
     */
    protected function header(string $header): void
    {
        header($header);
    }
}
