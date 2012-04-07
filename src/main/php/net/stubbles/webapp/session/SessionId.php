<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
use net\stubbles\lang\Object;
/**
 * Container for a session id.
 *
 * @since  2.0.0
 */
interface SessionId extends Object
{
    /**
     * returns session name
     *
     * @return  string
     */
    public function getName();

    /**
     * returns session id
     *
     * @return  string
     */
    public function get();

    /**
     * stores session id for given session name
     *
     * @return  SessionId
     */
    public function regenerate();

    /**
     * invalidates session id
     *
     * @return  SessionId
     */
    public function invalidate();
}
?>