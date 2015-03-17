<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\ioc;
use stubbles\webapp\request\WebRequest;
use stubbles\ioc\Binder;
use stubbles\ioc\module\BindingModule;
/**
 * Module to configure the binder with instances for request, session and response.
 *
 * @since  1.7.0
 */
class IoBindingModule implements BindingModule
{
    /**
     * marker whether runtime was already initialized
     *
     * @type  bool
     */
    private static $initialized = false;

    /**
     * checks whether runtime was already bound
     *
     * @internal
     * @return  bool
     */
    public static function initialized()
    {
        return self::$initialized;
    }

    /**
     * resets initialzed status
     *
     * @internal
     */
    public static function reset()
    {
        self::$initialized = false;
    }

    /**
     * response class to be used
     *
     * @type  string
     */
    private $responseClass  = 'stubbles\webapp\response\WebResponse';
    /**
     * function that creates the session instance
     *
     * @type  callable
     */
    private $sessionCreator;

    /**
     * constructor
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
     */
    public function __construct(callable $sessionCreator = null)
    {
        $this->sessionCreator = $sessionCreator;
        self::$initialized    = true;
    }

    /**
     * sets class name of response class to be used
     *
     * @param   string  $responseClass  name of response class to bind
     * @return  \stubbles\webapp\ioc\IoBindingModule
     * @since   1.1.0
     */
    public function setResponseClass($responseClass)
    {
        $this->responseClass = $responseClass;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  \stubbles\ioc\Binder  $binder
     */
    public function configure(Binder $binder)
    {
        $request       = WebRequest::fromRawSource();
        $responseClass = $this->responseClass;
        $response      = new $responseClass($request);
        $binder->bind('stubbles\input\web\WebRequest')
               ->toInstance($request);
        $binder->bind('stubbles\input\Request')
               ->toInstance($request);
        $binder->bind('stubbles\webapp\response\Response')
               ->toInstance($response);
        if (null !== $this->sessionCreator) {
            $sessionCreator = $this->sessionCreator;
            $session = $sessionCreator($request, $response);
            $binder->bind('stubbles\webapp\session\Session')->toInstance($session);
            $binder->setSession(new SessionAdapter($session));
        }
    }
}
