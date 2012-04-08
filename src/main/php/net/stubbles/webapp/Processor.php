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
use net\stubbles\lang\Object;
/**
 * Interface for processors.
 *
 * @api
 */
interface Processor extends Object
{
    /**
     * operations to be done before the request is processed
     *
     * @param   UriRequest  $uriRequest  called uri in this request
     * @return  Processor
     */
    public function startup(UriRequest $uriRequest);

    /**
     * checks whether the current request requires ssl or not
     *
     * @param   UriRequest  $uriRequest
     * @return  bool
     */
    public function requiresSsl(UriRequest $uriRequest);

    /**
     * processes the request
     *
     * @return  Processor
     */
    public function process();

    /**
     * operations to be done after the request was processed
     *
     * @return  Processor
     */
    public function cleanup();
}
?>