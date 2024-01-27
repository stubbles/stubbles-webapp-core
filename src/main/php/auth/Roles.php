<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Represents a list of roles.
 *
 * @since  5.0.0
 * @implements  \IteratorAggregate<string>
 */
class Roles implements Countable, IteratorAggregate
{
    /**
     * session key under which instance is stored within the session
     */
    const SESSION_KEY = 'stubbles.webapp.auth.roles';
    /** @var  array<string,int> */
    private array $roles;

    /**
     * @param  string[]  $roles
     */
    public function __construct(array $roles)
    {
        $this->roles = array_flip($roles);
    }

    /**
     * returns an empty role list
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * checks if given role is contained in list of roles
     */
    public function contain(string $roleName): bool
    {
        return isset($this->roles[$roleName]);
    }

    /**
     * returns amount of roles
     */
    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * returns an iterator that allows iterating over all roles
     *
     * @return  \Iterator<string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_flip($this->roles));
    }
}
