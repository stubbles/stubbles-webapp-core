<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
use stubbles\App;
use stubbles\ExceptionLogger;
use stubbles\ioc\Injector;
use stubbles\peer\MalformedUri;
use stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\SendableResponse;
use stubbles\webapp\response\WebResponse;
use stubbles\webapp\routing\UriResource;
use stubbles\webapp\routing\Routing;
use stubbles\webapp\session\Session;
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
    public function run(): SendableResponse
    {
        $request  = WebRequest::fromRawSource();
        $response = new WebResponse($request);
        if ($response->isFixed()) {
            return $response; // http version of request not supported
        }

        try {
            $requestUri = $request->uri();
        } catch (MalformedUri $mue) {
            $response->status()->badRequest();
            return $response;
        }

        $this->configureRouting($this->routing);
        $uriResource = $this->routing->findResource($requestUri, $request->method());
        if ($this->switchToHttps($request, $uriResource)) {
            $response->redirect($uriResource->httpsUri());
            return $response;
        }

        try {
            if (!$uriResource->negotiateMimeType($request, $response)) {
                return $response;
            }

            $this->sessionHandshake($request, $response);
            if ($uriResource->applyPreInterceptors($request, $response)) {
                $response->write($uriResource->resolve($request, $response));
                $uriResource->applyPostInterceptors($request, $response);
            }
        } catch (\Exception $e) {
            $this->injector->getInstance(ExceptionLogger::class)->log($e);
            $response->write($response->internalServerError($e->getMessage()));
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
                    Session::class
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
     * @param   \stubbles\webapp\Request              $request
     * @param   \stubbles\webapp\routing\UriResource  $uriResource
     * @return  bool
     */
    protected function switchToHttps(Request $request, UriResource $uriResource): bool
    {
        return !$request->isSsl() && $uriResource->requiresHttps();
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
    protected static function addAccessControlAllowOriginHeaderClass(): string
    {
        return AddAccessControlAllowOriginHeader::class;
    }
}
