<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    public function name(): string;

    /**
     * stores session id for given session name
     */
    public function regenerate(): self;

    /**
     * invalidates session id
     */
    public function invalidate(): self;

    /**
     * returns session id
     */
    public function __toString(): string;
}
