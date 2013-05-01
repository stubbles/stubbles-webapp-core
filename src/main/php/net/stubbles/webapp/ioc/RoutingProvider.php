<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\InjectionProvider;
use net\stubbles\ioc\Injector;
use net\stubbles\webapp\Routing;
use net\stubbles\webapp\UriRequest;
/**
 * Creates routing.
 *
 * @since  2.0.0
 */
class RoutingProvider implements InjectionProvider
{
    /**
     * request instance
     *
     * @type  WebRequest
     */
    private $request;
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  WebRequest  $request
     * @param  Injector    $injector
     * @Inject
     */
    public function __construct(WebRequest $request, Injector $injector)
    {
        $this->request  = $request;
        $this->injector = $injector;
    }

    /**
     * returns the value to provide
     *
     * @param   string  $name
     * @return  mixed
     */
    public function get($name = null)
    {
        return new Routing(new UriRequest($this->request->getUri(),
                                          $this->request->getMethod()
                           ),
                           $this->injector
        );
    }
}
?>