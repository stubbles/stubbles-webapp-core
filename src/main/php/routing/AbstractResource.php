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
    /**
     * injector instance
     *
     * @type  \stubbles\ioc\Injector
     */
    protected $injector;
    /**
     * actual called uri
     *
     * @type  \stubbles\webapp\routing\CalledUri
     */
    protected $calledUri;
    /**
     * interceptors to be processed
     *
     * @type  \stubbles\webapp\interceptor\Interceptors
     */
    private $interceptors;
    /**
     * list of available mime types for all routes
     *
     * @type  \stubbles\webapp\response\SupportedMimeTypes
     */
    private $supportedMimeTypes;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                       $injector
     * @param  \stubbles\webapp\routing\CalledUri           $calledUri           actual called uri
     * @param  \stubbles\webapp\routing\Interceptors        $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     */
    public function __construct(
            Injector $injector,
            CalledUri $calledUri,
            Interceptors $interceptors,
            SupportedMimeTypes $supportedMimeTypes)
    {
        $this->injector           = $injector;
        $this->calledUri          = $calledUri;
        $this->interceptors       = $interceptors;
        $this->supportedMimeTypes = $supportedMimeTypes;
    }

    /**
     * returns https uri of current route
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function httpsUri(): HttpUri
    {
        return $this->calledUri->toHttps();
    }

    /**
     * negotiates proper mime type for given request
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     * @since   6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response): bool
    {
        if ($this->supportedMimeTypes->isContentNegotationDisabled()) {
            return true;
        }

        $mimeType = $this->supportedMimeTypes->findMatch(
            $request->readHeader('HTTP_ACCEPT')
                    ->defaultingTo(emptyAcceptHeader())
                    ->withFilter(new AcceptFilter())
        );
        if (null === $mimeType) {
            $response->notAcceptable($this->supportedMimeTypes->asArray());
            return false;
        }

        if (!$this->supportedMimeTypes->provideClass($mimeType)) {
            $response->write($response->internalServerError(
                'No mime type class defined for negotiated content type ' . $mimeType
            ));
            return false;
        }

        $response->adjustMimeType(
            $this->injector->getInstance($this->supportedMimeTypes->classFor($mimeType))
                 ->specialise($mimeType)
        );
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
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(Request $request, Response $response): bool
    {
        return $this->interceptors->preProcess($request, $response);
    }

    /**
     * apply post interceptors
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(Request $request, Response $response): bool
    {
        return $this->interceptors->postProcess($request, $response);
    }
}
