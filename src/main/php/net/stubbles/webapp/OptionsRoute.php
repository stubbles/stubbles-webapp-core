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
use stubbles\input\web\WebRequest;
use net\stubbles\webapp\interceptor\Interceptors;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\SupportedMimeTypes;
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
     * @param  UriRequest          $calledUri           actual called uri
     * @param  Interceptors        $interceptors
     * @param  SupportedMimeTypes  $supportedMimeTypes
     * @param  string[]            $allowedMethods
     */
    public function __construct(UriRequest $calledUri,
                                Interceptors $interceptors,
                                SupportedMimeTypes $supportedMimeTypes,
                                array $allowedMethods)
    {
        parent::__construct($calledUri,
                            $interceptors,
                            $supportedMimeTypes
        );
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
    public function switchToHttps()
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        $response->addHeader('Allow', join(', ', $this->allowedMethods))
                 ->addHeader('Access-Control-Allow-Methods', join(', ', $this->allowedMethods));
        return true;
    }

}