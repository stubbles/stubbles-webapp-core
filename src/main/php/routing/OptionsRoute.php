<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\ioc\Injector;
use stubbles\webapp\UriRequest;
use stubbles\webapp\interceptor\Interceptors;
use stubbles\webapp\request\Request;
use stubbles\webapp\response\Response;
/**
 * Processable route which denotes an answer to an OPTIONS request when
 * no specific route for such requests was configured.
 *
 * @since  2.2.0
 */
class OptionsRoute extends AbstractProcessableRoute
{
    /**
     * list of actually allowed request methods
     *
     * @type  string[]
     */
    private $allowedMethods;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                       $injector
     * @param  \stubbles\webapp\UriRequest                  $calledUri           actual called uri
     * @param  \stubbles\webapp\interceptor\Interceptors    $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     * @param  string[]                                     $allowedMethods
     */
    public function __construct(
            Injector $injector,
            UriRequest $calledUri,
            Interceptors $interceptors,
            SupportedMimeTypes $supportedMimeTypes,
            array $allowedMethods)
    {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
        $this->allowedMethods = $allowedMethods;
        if (!in_array('OPTIONS', $this->allowedMethods)) {
            $this->allowedMethods[] = 'OPTIONS';
        }
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps()
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\request\Request    $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function process(Request $request, Response $response)
    {
        $response->addHeader('Allow', join(', ', $this->allowedMethods))
                 ->addHeader('Access-Control-Allow-Methods', join(', ', $this->allowedMethods));
        return true;
    }

}