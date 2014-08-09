<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use stubbles\lang\exception\Exception;
/**
 * Can be thrown when an auth provider experiences a problem which it can not solve.
 *
 * @since  2.3.0
 */
class AuthProviderException extends Exception
{
    /**
     * internal error
     */
    const INTERNAL = 500;
    /**
     * external error of upstream server
     */
    const EXTERNAL = 503;

    /**
     * constructor
     *
     * @param  int         $type
     * @param  string      $message
     * @param  \Exception  $cause
     * @param  int         $code
     */
    public function __construct($message, \Exception $cause = null, $code = 0)
    {
        parent::__construct($message, $cause, $code);

    }

    /**
     * creates an auth provider exception which denotes an internal error
     *
     * Should be used when the auth provider fails internally, i.e. can not reach
     * the database or something similar.
     *
     * @param   string      $message
     * @param   \Exception  $cause
     * @return  \stubbles\webapp\auth\AuthProviderException
     * @api
     */
    public static function internal($message, \Exception $cause = null)
    {
        return new self($message, $cause, self::INTERNAL);
    }

    /**
     * creates an auth provider exception which denotes an external error
     *
     * Should be used when the auth provider requires an external service to
     * validate the access and the external service fails.
     *
     * @param   string      $message
     * @param   \Exception  $cause
     * @return  \stubbles\webapp\auth\AuthProviderException
     * @api
     */
    public static function external($message, \Exception $cause = null)
    {
        return new self($message, $cause, self::EXTERNAL);
    }

    /**
     * checks whether the exception denotes an internal error
     *
     * @return  bool
     */
    public function isInternal()
    {
        return self::INTERNAL === $this->getCode();
    }
}
