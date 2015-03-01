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
use stubbles\lang\reflect;
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
        $this->annotations = reflect\annotationsOf($callback);
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
     * Roles aware means that a route might work different depending on the
     * roles a user has, but that access to the route in general is not
     * forbidden even if the user doesn't have any of the roles.
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
            return $this->annotations->firstNamed('RequiresRole')->getValue();
        }

        return null;
    }

    /**
     * checks whether route is annotated with @DisableContentNegotiation
     *
     * @return  bool
     * @since   5.1.0
     */
    public function isContentNegotiationDisabled()
    {
        return $this->annotations->contain('DisableContentNegotiation');
    }

    /**
     * returns a list of all mime types a route supports via @SupportsMimeType
     *
     * @return  string[]
     * @since   5.1.0
     */
    public function mimeTypes()
    {
        $mimeTypes = [];
        foreach ($this->annotations->named('SupportsMimeType') as $supportedMimeType) {
            $mimeTypes[] = $supportedMimeType->mimeType();
        }

        return $mimeTypes;
    }

    /**
     * returns a list of all formatters a route supports via @SupportsMimeType
     *
     * @return  string[]
     * @since   5.1.0
     */
    public function formatter()
    {
        $formatter = [];
        foreach ($this->annotations->named('SupportsMimeType') as $supportedMimeType) {
            if ($supportedMimeType->hasValueByName('formatter')) {
                $formatter[$supportedMimeType->mimeType()] = $this->formatterClass($supportedMimeType->formatter());
            }
        }

        return $formatter;
    }

    /**
     * returns class name of formatter
     *
     * @param   \ReflectionClass  $formatter
     * @return  string
     */
    private function formatterClass($formatter)
    {
        if ($formatter instanceof \ReflectionClass) {
            return $formatter->getName();
        }

        return $formatter;
    }
}
