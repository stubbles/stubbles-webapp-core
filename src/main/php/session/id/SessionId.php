<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\session
 */
namespace stubbles\webapp\session\id;
/**
 * Container for a session id.
 *
 * @since  2.0.0
 */
interface SessionId
{
    /**
     * returns session name
     *
     * @return  string
     */
    public function name(): string;

    /**
     * stores session id for given session name
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function regenerate(): self;

    /**
     * invalidates session id
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function invalidate(): self;

    /**
     * returns session id
     *
     * @return  string
     */
    public function __toString(): string;
}
