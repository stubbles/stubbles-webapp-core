<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\input\web\WebRequest;
use stubbles\ioc\App;
use stubbles\lang\errorhandler\ExceptionLogger;
use stubbles\peer\MalformedUriException;
use stubbles\webapp\ioc\IoBindingModule;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\ResponseNegotiator;
use stubbles\webapp\response\SupportedMimeTypes;
use stubbles\webapp\routing\ProcessableRoute;
use stubbles\webapp\routing\Routing;
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
     * @type  \stubbles\input\web\WebRequest
     */
    protected $request;
    /**
     * response negotiator
     *
     * @type  \stubbles\webapp\response\ResponseNegotiator
     */
    private $responseNegotiator;
    /**
     * build and contains routing information
     *
     * @type  \stubbles\webapp\routing\Routing
     */
    private $routing;
    /**
     * logger for logging uncatched exceptions
     *
     * @type  \stubbles\lang\errorhandler\ExceptionLogger
     */
    private $exceptionLogger;

    /**
     * constructor
     *
     * @param  \stubbles\input\web\WebRequest                $request             request data container
     * @param  \stubbles\webapp\response\ResponseNegotiator  $responseNegotiator  negoatiates based on request
     * @param  \stubbles\webapp\routing\Routing              $routing             routes to logic based on request
     * @param  \stubbles\lang\errorhandler\ExceptionLogger   $exceptionLogger     logs uncatched exceptions
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
     * runs the application but does not send the response
     *
     * @return  \stubbles\webapp\response\SendableResponse
     */
    public function run()
    {
        $this->configureRouting($this->routing);
        try {
            $route    = $this->routing->findRoute($this->request->uri(), $this->request->method());
            $response = $this->responseNegotiator->negotiateMimeType($this->request, $route->supportedMimeTypes());
            if (!$response->isFixed()) {
                $this->process($route, $response);
            }
        } catch (MalformedUriException $mue) {
            $response = $this->responseNegotiator->negotiateMimeType($this->request);
            $response->setStatusCode(400);
        }

        return $response;
    }

    /**
     * handles the request by processing the route
     *
     * @param  \stubbles\webapp\routing\ProcessableRoute  $route
     * @param  \stubbles\webapp\response\Response         $response
     */
    private function process(ProcessableRoute $route, Response $response)
    {
        if ($this->switchToHttps($route)) {
            $response->redirect($route->httpsUri());
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
     * @param   \stubbles\webapp\routing\ProcessableRoute  $route
     * @return  bool
     */
    protected function switchToHttps(ProcessableRoute $route)
    {
        return $route->requiresHttps();
    }

    /**
     * configures routing for this web app
     *
     * @param  \stubbles\webapp\routing\RoutingConfigurator  $routing
     */
    protected abstract function configureRouting(RoutingConfigurator $routing);

    /**
     * creates a web app instance via injection
     *
     * If the class to create an instance of contains a static __bindings() method
     * this method will be used to configure the ioc bindings before using the ioc
     * container to create the instance.
     *
     * @api
     * @param   string  $projectPath  path to project
     * @return  \stubbles\webapp\WebApp
     */
    public static function create($projectPath)
    {
        return self::createInstance(get_called_class(), $projectPath);
    }

    /**
     * creates a web app instance via injection
     *
     * @api
     * @param   string  $className    full qualified class name of class to create an instance of
     * @param   string  $projectPath  path to project
     * @return  \stubbles\webapp\WebApp
     */
    public static function createInstance($className, $projectPath)
    {
        IoBindingModule::reset();
        return parent::createInstance($className, $projectPath);
    }

    /**
     * creates list of bindings from given class
     *
     * @internal  must not be used by applications
     * @param   string  $className    full qualified class name of class to create an instance of
     * @param   string  $projectPath  path to project
     * @return  \stubbles\ioc\module\BindingModule[]
     */
    protected static function getBindingsForApp($className)
    {
        $bindings = parent::getBindingsForApp($className);
        if (!IoBindingModule::initialized()) {
            $bindings[] = self::createIoBindingModule();
        }

        return $bindings;
    }

    /**
     * creates io binding module
     *
     * The optional callable $sessionCreator can accept instances of
     * stubbles\input\web\WebRequest and stubbles\webapp\response\Response, and
     * must return an instance of stubbles\webapp\session\Session:
     * <code>
     * function(WebRequest $request, Response $response)
     * {
     *    return new MySession($request, $response);
     * }
     * </code>
     *
     * @param   callable  $sessionCreator  optional
     * @return  \stubbles\webapp\ioc\IoBindingModule
     */
    protected static function createIoBindingModule(callable $sessionCreator = null)
    {
        return new IoBindingModule($sessionCreator);
    }

    /**
     * returns post interceptor class which adds Access-Control-Allow-Origin header to the response
     *
     * @return  string
     * @since   3.4.0
     */
    protected static function addAccessControlAllowOriginHeaderClass()
    {
        return 'stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader';
    }
}
