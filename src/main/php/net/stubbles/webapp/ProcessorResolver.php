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
use net\stubbles\webapp\auth\AuthConfiguration;
use net\stubbles\webapp\auth\AuthProcessor;
use net\stubbles\webapp\response\Response;
/**
 * Resolver which creates processor instances.
 *
 * In case authentication is enabled the created processor will be decorated
 * with an auth processor.
 *
 * @since  2.0.0
 */
class ProcessorResolver extends BaseObject
{
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;
    /**
     * request instance
     *
     * @type  WebRequest
     */
    private $request;
    /**
     * response
     *
     * @type  Response
     */
    private $response;
    /**
     * auth configuration
     *
     * @type  AuthConfiguration
     */
    private $authConfig;

    /**
     * constructor
     *
     * @param  Injector    $injector
     * @param  WebRequest  $request
     * @param  Response    $response
     * @Inject
     */
    public function __construct(Injector $injector, WebRequest $request, Response $response)
    {
        $this->injector = $injector;
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * set auth configuration
     *
     * @param   AuthConfiguration  $authConfig
     * @return  ProcessorResolver
     * @Inject(optional=true)
     */
    public function setAuthConfig(AuthConfiguration $authConfig)
    {
        $this->authConfig = $authConfig;
        return $this;
    }
    /**
     * returns the processor
     *
     * @param   string|Closure  $processor
     * @return  mixed
     */
    public function resolve($processor)
    {
        return $this->decorateWithAuthProcessor($this->createProcessor($processor));
    }

    /**
     * creates processor instance
     *
     * @param   string|Closure  $processor
     * @return  ClosureProcessor
     */
    private function createProcessor($processor)
    {
        if ($processor instanceof \Closure) {
            return new ClosureProcessor($processor, $this->request, $this->response);
        }

        return $this->injector->getInstance($processor);
    }

    /**
     * decorates given processor with auth processor if auth is enabled
     *
     * @param   stubProcessor  $processor
     * @return  stubProcessor
     */
    private function decorateWithAuthProcessor(Processor $processor)
    {
        if (null === $this->authConfig) {
            return $processor;
        }

        return new AuthProcessor($processor,
                                 $this->response,
                                 $this->authConfig,
                                 $this->injector->getInstance('net\\stubbles\\webapp\\auth\\AuthHandler')
        );
    }
}
?>