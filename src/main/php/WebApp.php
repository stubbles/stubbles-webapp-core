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
use stubbles\ioc\App;
use stubbles\ioc\Injector;
use stubbles\lang\errorhandler\ExceptionLogger;
use stubbles\peer\MalformedUriException;
use stubbles\webapp\request\Request;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\ResponseNegotiator;
use stubbles\webapp\response\WebResponse;
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
     * @type  \stubbles\ioc\Injector
     */
    private $injector;
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
     * @param  \stubbles\ioc\Injector                        $injector
     * @param  \stubbles\webapp\response\ResponseNegotiator  $responseNegotiator  negoatiates based on request
     * @param  \stubbles\webapp\routing\Routing              $routing             routes to logic based on request
     * @param  \stubbles\lang\errorhandler\ExceptionLogger   $exceptionLogger     logs uncatched exceptions
     * @Inject
     */
    public function __construct(
            Injector $injector,
            ResponseNegotiator $responseNegotiator,
            Routing $routing,
            ExceptionLogger $exceptionLogger)
    {
        $this->injector           = $injector;
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
        $request = WebRequest::fromRawSource();
        $this->configureRouting($this->routing);
        try {
            $route    = $this->routing->findRoute($request->uri(), $request->method());
            $response = $this->responseNegotiator->negotiateMimeType($request, $route->supportedMimeTypes());
            if ($response->isFixed()) {
                return $response;
            }

            if ($this->switchToHttps($route)) {
                return $response->redirect($route->httpsUri());
            }

            $session = $request->attachSession(
                    $this->createSession($request, $response)
            );
            if (null !== $session) {
                $this->injector->setSession(
                        $session,
                        'stubbles\webapp\session\Session'
                );
            }

            if ($route->applyPreInterceptors($request, $response)) {
                if ($route->process($request, $response)) {
                    $route->applyPostInterceptors($request, $response);
                }
            }
        } catch (MalformedUriException $mue) {
            $response = new WebResponse($request);
            $response->setStatusCode(400);
        } catch (\Exception $e) {
            $this->exceptionLogger->log($e);
            $response->internalServerError($e->getMessage());
        }

        return $response;
    }

    /**
     * creates a session instance based on current request
     *
     * @param   \stubbles\webapp\request\Request    $request
     * @param   \stubbles\webapp\response\Response  $response
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    protected function createSession(Request $request, Response $response)
    {
        return null;
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
