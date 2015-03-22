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
use stubbles\ioc\Injector;
use stubbles\peer\http;
use stubbles\webapp\request\Request;
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
     * @param   \stubbles\webapp\request\Request              $request
     * @param   \stubbles\webapp\response\SupportedMimeTypes  $supportedMimeTypes  optional
     * @return  Response
     */
    public function negotiateMimeType(Request $request, SupportedMimeTypes $supportedMimeTypes = null)
    {
        if (null === $supportedMimeTypes || $supportedMimeTypes->isContentNegotationDisabled()) {
            return $this->response;
        }

        $mimeType = $supportedMimeTypes->findMatch($request->readHeader('HTTP_ACCEPT')
                                                           ->defaultingTo(http\emptyAcceptHeader())
                                                           ->withFilter(new AcceptFilter())
                    );
        if (null === $mimeType) {
            return $this->response->notAcceptable($supportedMimeTypes->asArray());
        }

        if (!$supportedMimeTypes->provideFormatter($mimeType)) {
            return $this->response->internalServerError('No formatter defined for negotiated content type ' . $mimeType);
        }

        return new FormattingResponse(
                $this->response,
                $this->injector->getInstance($supportedMimeTypes->formatterFor($mimeType)),
                $mimeType
        );
    }
}
