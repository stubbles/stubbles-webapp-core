<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
/**
 * Can be thrown when an auth provider experiences a problem which it can not solve.
 *
 * @since  2.3.0
 */
abstract class AuthProviderException extends \Exception
{
    /**
     * internal error
     */
    const INTERNAL = 500;
    /**
     * external error of upstream server
     */
    const EXTERNAL = 504;

    /**
     * constructor
     *
     * @param  string      $message
     * @param  \Throwable  $cause
     * @param  int         $code
     */
    public function __construct(string $message, \Throwable $cause = null, $code = 0)
    {
        parent::__construct($message, $code, $cause);

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
