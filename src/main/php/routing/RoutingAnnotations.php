<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\lang;
/**
 * Provides access to routing related annotations on a callback.
 *
 * @internal
 * @since  5.0.0
 */
class RoutingAnnotations
{
    /**
     * list of annotations for a callback
     *
     * @type  \stubbles\lang\reflect\annotation\Annotations
     */
    private $annotations;

    /**
     * constructor
     *
     * @param  string|callable|\stubbles\webapp\Processor  $callback
     */
    public function __construct($callback)
    {
        $this->annotations = lang\reflect($callback)->annotations();
    }

    /**
     * returns true if callback is annotated with @RequiresHttps
     *
     * @return  bool
     */
    public function requiresHttps()
    {
        return $this->annotations->contain('RequiresHttps');
    }

    /**
     * returns true if callback is annotated with @RequiresLogin
     *
     * @return  bool
     */
    public function requiresLogin()
    {
        return $this->annotations->contain('RequiresLogin');
    }

    /**
     * returns true if callback is annotated with @RolesAware
     *
     * @return  bool
     */
    public function rolesAware()
    {
        return $this->annotations->contain('RolesAware');
    }

    /**
     * returns role value if callback is annotated with @RequiresRole('someRole')
     *
     * @return  string
     */
    public function requiredRole()
    {
        if ($this->annotations->contain('RequiresRole')) {
            return $this->annotations->of('RequiresRole')[0]->getValue();
        }

        return null;
    }
}
