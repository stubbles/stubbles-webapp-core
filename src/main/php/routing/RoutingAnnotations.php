<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use ReflectionClass;
use stubbles\reflect\annotation\Annotation;
use stubbles\reflect\annotation\Annotations;
use stubbles\webapp\response\mimetypes\MimeType;
use stubbles\webapp\routing\api\Header;
use stubbles\webapp\routing\api\Parameter;
use stubbles\webapp\routing\api\Status;
use stubbles\webapp\Target;

use function stubbles\reflect\annotationsOf;
/**
 * Provides access to routing related annotations on a callback.
 *
 * @internal
 * @since  5.0.0
 */
class RoutingAnnotations
{
    private Annotations $annotations;

    /**
     * @param  class-string<Target>|callable|Target  $callback
     */
    public function __construct(
        string|callable|Target $callback
    ) {
        $this->annotations = annotationsOf($callback);
    }

    /**
     * returns true if callback is annotated with @RequiresHttps
     */
    public function requiresHttps(): bool
    {
        return $this->annotations->contain('RequiresHttps');
    }

    /**
     * returns true if callback is annotated with @RequiresLogin
     */
    public function requiresLogin(): bool
    {
        return $this->annotations->contain('RequiresLogin');
    }

    /**
     * returns true if callback is annotated with @RolesAware
     *
     * Roles aware means that a route might work different depending on the
     * roles a user has, but that access to the route in general is not
     * forbidden even if the user doesn't have any of the roles.
     */
    public function rolesAware(): bool
    {
        return $this->annotations->contain('RolesAware');
    }

    /**
     * returns role value if callback is annotated with @RequiresRole('someRole')
     */
    public function requiredRole(): ?string
    {
        if ($this->annotations->contain('RequiresRole')) {
            return $this->annotations->firstNamed('RequiresRole')->getValue();
        }

        return null;
    }

    /**
     * checks whether route is annotated with @DisableContentNegotiation
     *
     * @since  5.1.0
     */
    public function isContentNegotiationDisabled(): bool
    {
        return $this->annotations->contain('DisableContentNegotiation');
    }

    /**
     * returns a list of all mime types a route supports via @SupportsMimeType
     *
     * @return  string[]
     * @since   5.1.0
     */
    public function mimeTypes(): array
    {
        return array_map(
            fn(Annotation $supportedMimeType): string => $supportedMimeType->mimeType(),
            $this->annotations->named('SupportsMimeType')
        );
    }

    /**
     * returns a list of all mime type classes a route supports via @SupportsMimeType
     *
     * @return  string[]
     * @since   5.1.0
     */
    public function mimeTypeClasses(): array
    {
        $mimeTypeClasses = [];
        foreach ($this->annotations->named('SupportsMimeType') as $supportedMimeType) {
            if ($supportedMimeType->hasValueByName('class')) {
                $mimeTypeClasses[$supportedMimeType->mimeType()] = $this->nameForMimeTypeClass($supportedMimeType->class());
            }
        }

        return $mimeTypeClasses;
    }

    /**
     * returns class name of mime type class
     *
     * @param   class-string<MimeType>|ReflectionClass<MimeType>  $class
     * @return  class-string<MimeType>
     */
    private function nameForMimeTypeClass(string|ReflectionClass $class): string
    {
        if ($class instanceof ReflectionClass) {
            return $class->getName();
        }

        return $class;
    }

    /**
     * checks whether route should be ignored when building the API index
     *
     * @since  6.1.0
     */
    public function shouldBeIgnoredInApiIndex(): bool
    {
        return $this->annotations->contain('ExcludeFromApiIndex');
    }

    /**
     * checks whether a name is set
     *
     * @since  6.1.0
     */
    public function hasName(): bool
    {
        return $this->annotations->contain('Name');
    }

    /**
     * returns description of resource
     *
     * @since  6.1.0
     */
    public function name(): ?string
    {
        if ($this->annotations->contain('Name')) {
            return $this->annotations->firstNamed('Name')->getValue();
        }

        return null;
    }

    /**
     * returns description of resource
     *
     * @since  6.1.0
     */
    public function description(): ?string
    {
        if ($this->annotations->contain('Description')) {
            return $this->annotations->firstNamed('Description')->getValue();
        }

        return null;
    }

    /**
     * checks if any annotations of type Status are present
     *
     * @since  6.1.0
     */
    public function containStatusCodes(): bool
    {
        return $this->annotations->contain('Status');
    }

    /**
     * returns list of possible status codes on this route
     *
     * @return  Status[]
     * @since   6.1.0
     */
    public function statusCodes(): array
    {
        return array_map(
            fn(Annotation $status): Status => new Status($status->getCode(), $status->getDescription()),
            $this->annotations->named('Status')
        );
    }

    /**
     * checks if annotations of type Header are present
     *
     * @since  6.1.0
     */
    public function containHeaders(): bool
    {
        return $this->annotations->contain('Header');
    }

    /**
     * returns list of headers on this route
     *
     * @return  Header[]
     * @since   6.1.0
     */
    public function headers(): array
    {
        return array_map(
            fn(Annotation $header): Header => new Header($header->getName(), $header->getDescription()),
            $this->annotations->named('Header')
        );
    }

    /**
     * checks if annotations of type Parameter are present
     *
     * @since  6.1.0
     */
    public function containParameters(): bool
    {
        return $this->annotations->contain('Parameter');
    }

    /**
     * returns list of parameters
     *
     * @return  Parameter[]
     * @since   6.1.0
     */
    public function parameters(): array
    {
        return array_map(
            function(Annotation $parameter): Parameter
            {
                $param = new Parameter(
                    $parameter->getName(),
                    $parameter->getDescription(),
                    $parameter->getIn()
                );

                if ($parameter->hasValueByName('required') && $parameter->isRequired()) {
                    $param->markRequired();
                }

                return $param;
            },
            $this->annotations->named('Parameter')
        );
    }
}
