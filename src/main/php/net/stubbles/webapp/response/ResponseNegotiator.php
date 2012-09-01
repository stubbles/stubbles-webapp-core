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
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\Routing;
/**
 * Negotiates correct response for request.
 *
 * @since  2.0.0
 */
class ResponseNegotiator extends BaseObject
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
     * negotiates mime type based on accept header and configured mime types
     *
     * @param   WebRequest  $request
     * @param   Routing     $routing
     * @return  Response
     */
    public function negotiate(WebRequest $request, Routing $routing)
    {
        if (null === $request->getProtocolVersion()) {
            $this->response->setStatusCode(505)
                           ->write('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1');
            $request->cancel();
            return $this->response;
        }

        $mimeType = $routing->negotiateMimeType($request->readHeader('HTTP_ACCEPT')
                                                        ->applyFilter(new \net\stubbles\input\filter\AcceptFilter())
                        );
        if (null === $mimeType && $routing->canFindRouteWithAnyMethod()) {
            $this->response->setStatusCode(406)
                           ->addHeader('X-Acceptable',
                                       join(', ', $routing->getSupportedMimeTypes())
                             );
            $request->cancel();
            return $this->response;
        }

        $formatter = $this->createFormatter($mimeType);
        if (null === $formatter) {
            $this->response->setStatusCode(506)
                           ->write('No formatter defined for negotiated content type ' . $mimeType);
            $request->cancel();
            return $this->response;
        }

        return new FormattingResponse($this->response,
                                      $formatter,
                                      $mimeType
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