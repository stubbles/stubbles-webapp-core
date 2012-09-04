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
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\Routing;
use net\stubbles\webapp\UriRequest;
/**
 * Creates routing.
 *
 * @since  2.0.0
 */
class RoutingProvider extends BaseObject implements InjectionProvider
{
    /**
     * request instance
     *
     * @type  WebRequest
     */
    private $request;

    /**
     * constructor
     *
     * @param  WebRequest  $request
     * @Inject
     */
    public function __construct(WebRequest $request)
    {
        $this->request = $request;
    }

    /**
     * returns the value to provide
     *
     * @param   string  $name
     * @return  mixed
     */
    public function get($name = null)
    {
        return new Routing(new UriRequest($this->request->getUri(), $this->request->getMethod()));
    }
}
?>