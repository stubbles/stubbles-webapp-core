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
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\Injector;
use net\stubbles\webapp\Routing;
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
        $httpVersion = $request->getProtocolVersion();
        if (null === $httpVersion) {
            $response = new $responseClass();
            $response->httpVersionNotSupported();
            $request->cancel();
            return $response;
        }

        return new $responseClass($httpVersion);
    }

    /**
     * negotiates mime type based on accept header and configured mime types
     *
     * In case the request was already cancelled no negotation takes place.
     * Forces a 406 Not Acceptable response in case none of the accepted user
     * agent mime types is supported. Forces a 500 Internal Server Error
     * response in case a mime type but no suitable formatter was found.
     *
     * @param   WebRequest  $request
     * @param   Routing     $routing
     * @return  Response
     */
    public function negotiateMimeType(WebRequest $request, Routing $routing)
    {
        if ($request->isCancelled()) {
            return $this->response;
        }

        $mimeType = $routing->negotiateMimeType($request->readHeader('HTTP_ACCEPT')
                                                        ->applyFilter(new \net\stubbles\input\filter\AcceptFilter())
                    );
        if (null === $mimeType && $routing->canFindRouteWithAnyMethod()) {
            $this->response->notAcceptable($routing->getSupportedMimeTypes());
            $request->cancel();
            return $this->response;
        }

        $formatter = $this->createFormatter($mimeType);
        if (null === $formatter) {
            $this->response->internalServerError('No formatter defined for negotiated content type ' . $mimeType);
            $request->cancel();
            return $this->response;
        }

        if (null !== $mimeType) {
            $this->response->addHeader('Content-type', $mimeType);
        }

        return new FormattingResponse($this->response,
                                      $formatter
        );
    }

    /**
     * creates formatter instance
     *
     * @param   string  $mimeType
     * @return  format\Formatter
     */
    private function createFormatter($mimeType)
    {
        if (null === $mimeType) {
            return new format\VoidFormatter();
        }

        if ($this->injector->hasBinding('net\stubbles\webapp\response\format\Formatter', $mimeType)) {
            return $this->injector->getInstance('net\stubbles\webapp\response\format\Formatter', $mimeType);
        }

        return null;
    }
}
?>