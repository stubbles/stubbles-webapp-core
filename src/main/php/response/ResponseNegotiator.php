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
     * injector instance
     *
     * @type  \stubbles\ioc\Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector              $injector  injector to create required formatter with
     * @Inject
     */
    public function __construct(Injector $injector)
    {
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
     * @param   \stubbles\webapp\response\SupportedMimeTypes  $supportedMimeTypes  optional
     * @return  \stubbles\webapp\response\Response
     */
    public function negotiateMimeType(WebRequest $request, SupportedMimeTypes $supportedMimeTypes = null)
    {
        if (null === $supportedMimeTypes || $supportedMimeTypes->isContentNegotationDisabled()) {
            return new WebResponse($request, new mimetypes\PassThrough());
        }

        $mimeType = $supportedMimeTypes->findMatch($request->readHeader('HTTP_ACCEPT')
                                                           ->defaultingTo(http\emptyAcceptHeader())
                                                           ->withFilter(new AcceptFilter())
                    );
        if (null === $mimeType) {
            $response = new WebResponse($request, new mimetypes\PassThrough());
            return $response->notAcceptable($supportedMimeTypes->asArray());

        }

        if (!$supportedMimeTypes->provideFormatter($mimeType)) {
            $response = new WebResponse($request, new mimetypes\PassThrough());
            return $response->internalServerError('No formatter defined for negotiated content type ' . $mimeType);
        }

        return new WebResponse(
                $request,
                $this->injector->getInstance($supportedMimeTypes->formatterFor($mimeType))->specialise($mimeType)
        );
    }
}
