<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\Injector;
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\response\Response;
/**
 * Frontend controller for web applications.
 *
 * @since  1.7.0
 */
class WebAppFrontController extends BaseObject
{
    /**
     * contains request data
     *
     * @type  WebRequest
     */
    private $request;
    /**
     * response container
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
     * resolves processor
     *
     * @type  ProcessorResolver
     */
    private $resolver;
    /**
     * config which interceptors and processors should respond to which uri
     *
     * @type  UriConfiguration
     */
    private $uriConfig;

    /**
     * constructor
     *
     * @param  WebRequest         $request    request data container
     * @param  Response           $response   response container
     * @param  Injector           $injector
     * @param  ProcessorResolver  $resolver   injector instance to create interceptor and processor instances
     * @param  UriConfiguration   $uriConfig  config which interceptors and processors should respond to which uri
     * @Inject
     */
    public function __construct(WebRequest $request,
                                Response $response,
                                Injector $injector,
                                ProcessorResolver $resolver,
                                UriConfiguration $uriConfig)
    {
        $this->request   = $request;
        $this->response  = $response;
        $this->injector  = $injector;
        $this->resolver  = $resolver;
        $this->uriConfig = $uriConfig;
    }

    /**
     * does the whole processing
     */
    public function process()
    {
        if (!$this->request->isCancelled()) {
            $calledUri = new UriRequest($this->request->getUri());
            if ($this->applyPreInterceptors($calledUri)) {
                if ($this->applyProcessor($calledUri)) {
                    $this->applyPostInterceptors($calledUri);
                }
            }
        }

        $this->response->send();
    }

    /**
     * apply configured pre interceptors to called uri
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   UriRequest  $calledUri
     * @return  bool
     */
    private function applyPreInterceptors(UriRequest $calledUri)
    {
        foreach ($this->uriConfig->getPreInterceptors($calledUri) as $interceptorClassName) {
            $this->injector->getInstance($interceptorClassName)
                           ->preProcess($this->request, $this->response);
            if ($this->request->isCancelled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * apply configured processor to called uri
     *
     * Returns false if the processor cancels the request, throws an exception
     * or processor requires ssl but current request is not in ssl.
     *
     * @param   UriRequest  $calledUri
     * @return  bool
     */
    private function applyProcessor(UriRequest $calledUri)
    {
        $processor = null;
        try {
            $processor = $this->resolver->resolve($this->uriConfig->getProcessorForUri($calledUri));
            $processor->startup($calledUri);
            if (!$calledUri->isSsl() && $processor->requiresSsl($calledUri)) {
                $this->response->redirect($calledUri->toHttps());
                $this->request->cancel();
                return false;
            }

            $processor->process();
        } catch (ProcessorException $pe) {
            $this->response->setStatusCode($pe->getStatusCode());
            $this->request->cancel();
        }

        if (null !== $processor) {
            $processor->cleanup();
        }

        if ($this->request->isCancelled()) {
            return false;
        }

        return true;
    }

    /**
     * apply configured post interceptors to called uri
     *
     * @param  UriRequest  $calledUri
     */
    private function applyPostInterceptors(UriRequest $calledUri)
    {
        foreach ($this->uriConfig->getPostInterceptors($calledUri) as $interceptorClassName) {
            $this->injector->getInstance($interceptorClassName)
                           ->postProcess($this->request, $this->response);
            if ($this->request->isCancelled()) {
                return;
            }
        }
    }
}
?>