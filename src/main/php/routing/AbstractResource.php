<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\input\filter\AcceptFilter;
use stubbles\peer\http\HttpUri;
use stubbles\ioc\Injector;
use stubbles\webapp\Request;
use stubbles\webapp\Response;

use function stubbles\peer\http\emptyAcceptHeader;
/**
 * Contains logic to process the route.
 *
 * @since  2.2.0
 */
abstract class AbstractResource implements UriResource
{
    public function __construct(
        protected Injector $injector,
        protected CalledUri $calledUri,
        private Interceptors $interceptors,
        private SupportedMimeTypes $supportedMimeTypes
    ) { }

    /**
     * returns https uri of current route
     */
    public function httpsUri(): HttpUri
    {
        return $this->calledUri->toHttps();
    }

    /**
     * negotiates proper mime type for given request
     *
     * @since  6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response): bool
    {
        if ($this->supportedMimeTypes->isContentNegotationDisabled()) {
            return true;
        }

        $matchedMimeType = $this->supportedMimeTypes->findMatch(
            $request->readHeader('HTTP_ACCEPT')
                ->defaultingTo(emptyAcceptHeader())
                ->withFilter(new AcceptFilter())
        );
        if (null === $matchedMimeType) {
            $response->notAcceptable($this->supportedMimeTypes->asArray());
            return false;
        }

        if (!$this->supportedMimeTypes->provideClass($matchedMimeType)) {
            $response->write(
                $response->internalServerError(
                    sprintf(
                        'No mime type class defined for negotiated content type %s',
                        $matchedMimeType
                    )
                )
            );
            return false;
        }

        /** @var  \stubbles\webapp\response\mimetypes\MimeType  $mimeType */
        $mimeType = $this->injector->getInstance(
            $this->supportedMimeTypes->classFor($matchedMimeType)
        );
        $response->adjustMimeType($mimeType->specialise($matchedMimeType));
        return true;
    }

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function supportedMimeTypes(): array
    {
        return $this->supportedMimeTypes->asArray();
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     */
    public function applyPreInterceptors(Request $request, Response $response): bool
    {
        return $this->interceptors->preProcess($request, $response);
    }

    /**
     * apply post interceptors
     */
    public function applyPostInterceptors(Request $request, Response $response): bool
    {
        return $this->interceptors->postProcess($request, $response);
    }
}
