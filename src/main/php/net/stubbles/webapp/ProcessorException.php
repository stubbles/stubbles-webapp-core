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
use net\stubbles\lang\exception\Exception;
/**
 * Exception to be thrown if an error occurs while processor handling.
 */
class ProcessorException extends Exception
{
    /**
     * status code of processing failure
     *
     * @type  int
     */
    protected $statusCode;

    /**
     * constructor
     *
     * @param  int         $statusCode
     * @param  string      $message
     * @param  \Exception  $cause
     */
    public function __construct($statusCode, $message, \Exception $cause = null)
    {
        parent::__construct($message, $cause);
        $this->statusCode = $statusCode;
    }

    /**
     * returns status code
     *
     * @return  int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
?>