<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private $code;
    /**
     * reason phrase for status code
     *
     * @type  string
     */
    private $reasonPhrase;
    /**
     * switch whether response is fixed or not
     *
     * @type  bool
     */
    private $fixed         = false;
    /**
     * list of headers for this response
     *
     * @type  \stubbles\webapp\response\Headers
     */
    private $headers;
    /**
     * whether response code allows a response payload
     *
     * @type  bool
     */
    private $allowsPayload = true;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\response\Headers  $headers
     */
    public function __construct(Headers $headers)
    {
        $this->headers = $headers;
        $this->setCode(200);
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
    public function setCode(int $code, string $reasonPhrase = null): self
    {
        $this->code         = $code;
        $this->reasonPhrase = null === $reasonPhrase ? Http::reasonPhraseFor($code) : $reasonPhrase;
        return $this;
    }

    /**
     * sets status code and sets status to fixed
     *
     * @param   int  $code
     * @return  \stubbles\webapp\response\Status
     */
    private function fixateCode(int $code): self
    {
        $this->setCode($code);
        $this->fixed = true;
        return $this;
    }

    /**
     * sets status to 201 Created
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri  uri under which created resource can be found
     * @param   string                              $etag  optional  entity-tag of the newly created resource's representation
     * @return  \stubbles\webapp\response\Status
     */
    public function created($uri, string $etag = null): self
    {
        $this->headers->location($uri);
        if (null !== $etag) {
            $this->headers->add('ETag', $etag);
        }

        return $this->fixateCode(201);
    }

    /**
     * sets status code to 202 Accepted
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function accepted(): self
    {
        return $this->fixateCode(202);
    }

    /**
     * sets status code to 204 No Content
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function noContent(): self
    {
        $this->allowsPayload = false;
        $this->headers->add('Content-Length', 0);
        return $this->fixateCode(204);
    }

    /**
     * sets status code to 205 Reset Content
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function resetContent(): self
    {
        $this->allowsPayload = false;
        $this->headers->add('Content-Length', 0);
        return $this->fixateCode(205);
    }

    /**
     * sets status code to 206 Partial Content
     *
     * @param   int|string  $lower      lower border of range
     * @param   int|string  $upper      upper border of range
     * @param   int|string  $total      optional    total length of content, defaults to * for "unknown"
     * @param   string      $rangeUnit  optional    range unit, defaults to "bytes"
     * @return  \stubbles\webapp\response\Status
     */
    public function partialContent($lower, $upper, $total = '*', string $rangeUnit = 'bytes'): self
    {
        $this->headers->add(
                'Content-Range',
                $rangeUnit . ' ' . $lower . '-' . $upper  . '/' . $total
        );
        return $this->fixateCode(206);
    }

    /**
     * sets status to 30x
     *
     * @param   string|\stubbles\peer\http\HttpUri  $uri         http uri to redirect to
     * @param   int                                 $statusCode  HTTP status code to redirect with (301, 302, ...)
     * @return  \stubbles\webapp\response\Status
     */
    public function redirect($uri, int $statusCode = 302): self
    {
        $this->headers->location($uri);
        return $this->fixateCode($statusCode);
    }

    /**
     * sets status to 304 Not Modified
     *
     * @return  \stubbles\webapp\response\Status
     * @todo enforce any of Cache-Control, Content-Location, Date, ETag, Expires, and Vary.
     */
    public function notModified(): self
    {
        return $this->fixateCode(304);
    }

    /**
     * sets status to 400 Bad Request
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function badRequest(): self
    {
        return $this->fixateCode(400);
    }

    /**
     * sets status to 401 Unauthorized
     *
     * @param   string[]  $challenges  list of challenges
     * @return  \stubbles\webapp\response\Status
     * @throws  \InvalidArgumentException  in case $challenges is empty
     */
    public function unauthorized(array $challenges): self
    {
        if (count($challenges) === 0) {
            throw new \InvalidArgumentException('Challenges must contain at least one entry');
        }

        $this->headers->add('WWW-Authenticate', join(', ', $challenges));
        return $this->fixateCode(401);
    }

    /**
     * sets status to 403 Forbidden
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function forbidden(): self
    {
        return $this->fixateCode(403);
    }

    /**
     * sets status to 404 Not Found
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function notFound(): self
    {
        return $this->fixateCode(404);
    }

    /**
     * sets status to 405 Method Not Allowed
     *
     * @param   string[]  $allowedMethods
     * @return  \stubbles\webapp\response\Status
     */
    public function methodNotAllowed(array $allowedMethods): self
    {
        $this->headers->allow($allowedMethods);
        return $this->fixateCode(405);
    }

    /**
     * sets status to 406 Not Acceptable
     *
     * @param   string[]  $supportedMimeTypes  list of supported mime types
     * @return  \stubbles\webapp\response\Status
     */
    public function notAcceptable(array $supportedMimeTypes = []): self
    {
        $this->headers->acceptable($supportedMimeTypes);
        return $this->fixateCode(406);
    }

    /**
     * sets status to 409 Conflict
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function conflict(): self
    {
        return $this->fixateCode(409);
    }

    /**
     * sets status to 410 Gone
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function gone(): self
    {
        return $this->fixateCode(410);
    }

    /**
     * sets status to 411 Length Required
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function lengthRequired(): self
    {
        return $this->fixateCode(411);
    }

    /**
     * sets status to 412 Precondition Failed
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function preconditionFailed(): self
    {
        return $this->fixateCode(412);
    }

    /**
     * sets status to 415 Unsupported Media Type
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function unsupportedMediaType(): self
    {
        return $this->fixateCode(415);
    }

    /**
     * sets status to 416 Range Not Satisfiable
     *
     * @param   int|string  $total      total length of content
     * @param   string      $rangeUnit  optional  range unit, defaults to "bytes"
     * @return  \stubbles\webapp\response\Status
     */
    public function rangeNotSatisfiable($total, string $rangeUnit = 'bytes'): self
    {
        $this->headers->add('Content-Range', $rangeUnit . ' */' . $total);
        return $this->fixateCode(416);
    }

    /**
     * sets status to 500 Internal Server Error
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function internalServerError(): self
    {
        return $this->fixateCode(500);
    }

    /**
     * sets status to 501 Not Implemented
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function notImplemented(): self
    {
        return $this->fixateCode(501);
    }

    /**
     * sets status to 503 Service Unavailable
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function serviceUnavailable(): self
    {
        return $this->fixateCode(503);
    }

    /**
     * sets status to 505 HTTP Version Not Supported
     *
     * @return  \stubbles\webapp\response\Status
     */
    public function httpVersionNotSupported(): self
    {
        return $this->fixateCode(505);
    }

    /**
     * returns current status code
     *
     * @return  int
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * a status is fixed when a final status has been set
     *
     * @return  bool
     */
    public function isFixed(): bool
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
    public function line($httpVersion, string $sapi = PHP_SAPI): string
    {
        if ('cgi' === $sapi) {
            return 'Status: ' . $this->code . ' ' . $this->reasonPhrase;
        }

        return $httpVersion . ' ' . $this->code . ' ' . $this->reasonPhrase;
    }

    /**
     * whether response is allowed to contain a payload
     *
     * @return  bool
     */
    public function allowsPayload(): bool
    {
        return $this->allowsPayload;
    }
}
