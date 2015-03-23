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
use stubbles\peer\MalformedUriException;
use stubbles\webapp\request\WebRequest;
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
     * build and contains routing information
     *
     * @type  \stubbles\webapp\routing\Routing
     */
    private $routing;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector            $injector
     * @param  \stubbles\webapp\routing\Routing  $routing   routes to logic based on request
     * @Inject
     */
    public function __construct(Injector $injector, Routing $routing)
    {
        $this->injector = $injector;
        $this->routing  = $routing;
    }

    /**
     * runs the application but does not send the response
     *
     * @return  \stubbles\webapp\response\SendableResponse
     */
    public function run()
    {
        $request  = WebRequest::fromRawSource();
        $response = new WebResponse($request);
        if ($response->isFixed()) {
            return $response; // http version of request not supported
        }

        try {
            $requestUri = $request->uri();
        } catch (MalformedUriException $mue) {
            $response->status()->badRequest();
            return $response;
        }

        $this->configureRouting($this->routing);
        $route = $this->routing->findRoute($requestUri, $request->method());
        if ($this->switchToHttps($route)) {
            $response->status()->redirect($route->httpsUri());
            return $response;
        }

        try {
            if (!$route->negotiateMimeType($request, $response)) {
                return $response;
            }

            $this->sessionHandshake($request, $response);
            if ($route->applyPreInterceptors($request, $response)) {
                if ($route->process($request, $response)) {
                    $route->applyPostInterceptors($request, $response);
                }
            }
        } catch (\Exception $e) {
            $this->injector->getInstance(
                    'stubbles\lang\errorhandler\ExceptionLogger'
            )->log($e);
            $response->internalServerError($e->getMessage());
        }

        return $response;
    }

    /**
     * ensures session is present when created
     *
     * @param  \stubbles\webapp\Request   $request
     * @param  \stubbles\webapp\Response  $response
     */
    private function sessionHandshake(Request $request, Response $response)
    {
        $session = $this->createSession($request, $response);
        if (null !== $session) {
            $this->injector->setSession(
                    $request->attachSession($session),
                    'stubbles\webapp\session\Session'
            );
        }
    }

    /**
     * creates a session instance based on current request
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response
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
