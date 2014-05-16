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
use net\stubbles\ioc\App;
use net\stubbles\lang\errorhandler\ExceptionLogger;
use net\stubbles\webapp\ioc\IoBindingModule;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\ResponseNegotiator;
/**
 * Abstract base class for web applications.
 *
 * @since  1.7.0
 */
abstract class WebApp extends App
{
    /**
     * contains request data
     *
     * @type  WebRequest
     */
    protected $request;
    /**
     * response negotiator
     *
     * @type  ResponseNegotiator
     */
    private $responseNegotiator;
    /**
     * build and contains routing information
     *
     * @type  Routing
     */
    private $routing;
    /**
     * logger for logging uncatched exceptions
     *
     * @type  ExceptionLogger
     */
    private $exceptionLogger;

    /**
     * constructor
     *
     * @param  WebRequest          $request             request data container
     * @param  ResponseNegotiator  $responseNegotiator  negoatiates based on request
     * @param  Routing             $routing             routes to logic based on request
     * @param  ExceptionLogger     $exceptionLogger     logs uncatched exceptions
     * @Inject
     */
    public function __construct(WebRequest $request,
                                ResponseNegotiator $responseNegotiator,
                                Routing $routing,
                                ExceptionLogger $exceptionLogger)
    {
        $this->request            = $request;
        $this->responseNegotiator = $responseNegotiator;
        $this->routing            = $routing;
        $this->exceptionLogger    = $exceptionLogger;
    }

    /**
     * runs the application
     *
     * @return  Response
     */
    public function run()
    {
        $this->configureRouting($this->routing);
        $route    = $this->routing->findRoute(new UriRequest($this->request->getUri(),
                                                             $this->request->getMethod()
                                              )
                    );
        $response = $this->responseNegotiator->negotiateMimeType($this->request, $route->getSupportedMimeTypes());
        if (!$response->isFixed()) {
            $this->process($route, $response);
        }

        $this->send($response);
        return $response;
    }

    /**
     * handles the request by processing the route
     *
     * @param  ProcessableRoute  $route
     * @param  Response          $response
     */
    private function process(ProcessableRoute $route, Response $response)
    {
        if ($this->switchToHttps($route)) {
            $response->redirect($route->getHttpsUri());
            return;
        }

        try {
            if ($route->applyPreInterceptors($this->request, $response)) {
                if ($route->process($this->request, $response)) {
                    $route->applyPostInterceptors($this->request, $response);
                }
            }
        } catch (\Exception $e) {
            $this->exceptionLogger->log($e);
            $response->internalServerError($e->getMessage());
        }
    }

    /**
     * checks whether a switch to https must be made
     *
     * @param   ProcessableRoute  $route
     * @return  bool
     */
    protected function switchToHttps(ProcessableRoute $route)
    {
        return $route->switchToHttps();
    }

    /**
     * sends response
     *
     * @param  Response  $response
     */
    protected function send(Response $response)
    {
        if ($this->request->getMethod() === 'HEAD') {
            $response->sendHead();
        } else {
            $response->send();
        }
    }

    /**
     * configures routing for this web app
     *
     * @param  RoutingConfigurator  $routing
     */
    protected abstract function configureRouting(RoutingConfigurator $routing);

    /**
     * creates io binding module with session
     *
     * @param   string  $sessionName
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithSession($sessionName = 'PHPSESSID')
    {
        return IoBindingModule::createWithSession($sessionName);
    }

    /**
     * creates io binding module without session
     *
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithoutSession()
    {
        return IoBindingModule::createWithoutSession();
    }

    /**
     * returns post interceptor class which adds Access-Control-Allow-Origin header to the response
     *
     * @return  string
     * @since   3.4.0
     */
    protected static function addAccessControlAllowOriginHeaderClass()
    {
        return 'net\stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader';
    }
}
