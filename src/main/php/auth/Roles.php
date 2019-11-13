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
 * Represents a list of roles.
 *
 * @since  5.0.0
 */
class Roles implements \Countable, \IteratorAggregate
{
    /**
     * session key under which instance is stored within the session
     */
    const SESSION_KEY = 'stubbles.webapp.auth.roles';
    /**
     * list of roles
     *
     * @type  array
     */
    private $roles;

    /**
     * constructor
     *
     * @param  string[]  $roles
     */
    public function __construct(array $roles)
    {
        $this->roles = array_flip($roles);
    }

    /**
     * returns an empty role list
     *
     * @return  \stubbles\webapp\auth\Roles
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * checks if given role is contained in list of roles
     *
     * @param   string  $roleName
     * @return  bool
     */
    public function contain(string $roleName): bool
    {
        return isset($this->roles[$roleName]);
    }

    /**
     * returns amount of roles
     *
     * @return  int
     */
    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * returns an iterator that allows iterating over all roles
     *
     * @return  \ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_flip($this->roles));
    }
}
