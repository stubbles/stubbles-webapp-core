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
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\response\Response;
/**
 * Processor which decorates a closure that handles the request.
 */
class ClosureProcessor extends BaseObject implements Processor
{
    /**
     * closure which handles the request
     *
     * @type  Closure
     */
    private $closure;
    /**
     * request instance
     *
     * @type  WebRequest
     */
    private $request;
    /**
     * response instance
     *
     * @type  Response
     */
    private $response;

    /**
     * constructor
     *
     * @param  Closure     $closure
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function __construct(\Closure $closure, WebRequest $request, Response $response)
    {
        $this->closure  = $closure;
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * operations to be done before the request is processed
     *
     * @param   UriRequest  $uriRequest  called uri in this request
     * @return  Processor
     */
    public function startup(UriRequest $uriRequest)
    {
        return $this;
    }

    /**
     * checks whether the current request requires ssl or not
     *
     * @param   UriRequest  $uriRequest
     * @return  bool
     */
    public function requiresSsl(UriRequest $uriRequest)
    {
        return false;
    }

    /**
     * processes the request
     *
     * @return  Processor
     */
    public function process()
    {
        $closure = $this->closure;
        $closure($this->request, $this->response);
        return $this;
    }

    /**
     * operations to be done after the request was processed
     *
     * @return  Processor
     */
    public function cleanup()
    {
        return $this;
    }
}
?>