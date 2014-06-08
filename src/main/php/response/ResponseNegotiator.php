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
use stubbles\input\filter\AcceptFilter;
use stubbles\input\web\WebRequest;
use stubbles\ioc\Injector;
/**
 * Negotiates correct response for request.
 *
 * @since  2.0.0
 */
class ResponseNegotiator
{
    /**
     * base response implementation
     *
     * @type  Response
     */
    private $response;
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  Response  $response  base response implementation
     * @param  Injector  $injector  injector to create required formatter with
     * @Inject
     */
    public function __construct(Response $response,
                                Injector $injector)
    {
        $this->response = $response;
        $this->injector = $injector;
    }

    /**
     * negotiates http response for http version
     *
     * Forces 505 HTTP Version Not Supported response in case the request
     * has a non-supported protocol version and cancels the request.
     *
     * @param   WebRequest  $request
     * @param   string      $responseClass
     * @return  Response
     */
    public static function negotiateHttpVersion(WebRequest $request, $responseClass = 'net\stubbles\webapp\response\WebResponse')
    {
        $httpVersion = $request->protocolVersion();
        if (null === $httpVersion) {
            $response = new $responseClass();
            return $response->httpVersionNotSupported();
        }

        return new $responseClass($httpVersion);
    }

    /**
     * negotiates mime type based on accept header and configured mime types
     *
     * Forces a 406 Not Acceptable response in case none of the accepted user
     * agent mime types is supported. Forces a 500 Internal Server Error
     * response in case a mime type but no suitable formatter was found.
     *
     * @param   WebRequest          $request
     * @param   SupportedMimeTypes  $supportedMimeTypes
     * @return  Response
     */
    public function negotiateMimeType(WebRequest $request, SupportedMimeTypes $supportedMimeTypes)
    {
        if ($supportedMimeTypes->isContentNegotationDisabled()) {
            return $this->response;
        }

        $mimeType = $supportedMimeTypes->findMatch($request->readHeader('HTTP_ACCEPT')
                                                           ->withFilter(new AcceptFilter())
                    );
        if (null === $mimeType) {
            return $this->response->notAcceptable($supportedMimeTypes->asArray());
        }

        $formatter = $this->createFormatter($mimeType, $supportedMimeTypes);
        if (null === $formatter) {
            return $this->response->internalServerError('No formatter defined for negotiated content type ' . $mimeType);
        }

        return new FormattingResponse($this->response, $formatter, $mimeType);
    }

    /**
     * creates formatter instance
     *
     * @param   string  $mimeType
     * @return  format\Formatter
     */
    private function createFormatter($mimeType, SupportedMimeTypes $supportedMimeTypes)
    {
        if ($supportedMimeTypes->hasFormatter($mimeType)) {
            return $this->injector->getInstance($supportedMimeTypes->getFormatter($mimeType));
        }

        if ($this->injector->hasBinding('net\stubbles\webapp\response\format\Formatter', $mimeType)) {
            return $this->injector->getInstance('net\stubbles\webapp\response\format\Formatter', $mimeType);
        }

        return null;
    }
}
