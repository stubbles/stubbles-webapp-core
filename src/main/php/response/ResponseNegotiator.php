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
use stubbles\input\filter\AcceptFilter;
use stubbles\input\web\WebRequest;
use stubbles\ioc\Injector;
use stubbles\peer\http;
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
     * @type  \stubbles\webapp\response\Response
     */
    private $response;
    /**
     * injector instance
     *
     * @type  \stubbles\ioc\Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\response\Response  $response  base response implementation
     * @param  \stubbles\ioc\Injector              $injector  injector to create required formatter with
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
     * Forces a 406 Not Acceptable response in case none of the accepted user
     * agent mime types is supported. Forces a 500 Internal Server Error
     * response in case a mime type but no suitable formatter was found.
     *
     * @param   \stubbles\input\web\WebRequest                $request
     * @param   \stubbles\webapp\response\SupportedMimeTypes  $supportedMimeTypes
     * @return  Response
     */
    public function negotiateMimeType(WebRequest $request, SupportedMimeTypes $supportedMimeTypes)
    {
        if ($supportedMimeTypes->isContentNegotationDisabled()) {
            return $this->response;
        }

        $mimeType = $supportedMimeTypes->findMatch($request->readHeader('HTTP_ACCEPT')
                                                           ->defaultingTo(http\emptyAcceptHeader())
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
     * @return  \stubbles\webapp\response\format\Formatter
     */
    private function createFormatter($mimeType, SupportedMimeTypes $supportedMimeTypes)
    {
        if ($supportedMimeTypes->provideFormatter($mimeType)) {
            return $this->injector->getInstance($supportedMimeTypes->formatterFor($mimeType));
        }

        if ($this->injector->hasBinding('stubbles\webapp\response\format\Formatter', $mimeType)) {
            return $this->injector->getInstance('stubbles\webapp\response\format\Formatter', $mimeType);
        }

        return null;
    }
}
