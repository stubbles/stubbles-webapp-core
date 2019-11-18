<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\{RoutingAnnotations, UriResource};

use function bovigo\assert\{
    assertThat,
    assertNull,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isSameAs
};
/**
 * Test for specific situation which shouldn't occur but must be taken care of in code.
 *
 * @since  8.0.0
 */
class AuthConstraintTest extends TestCase
{
    /**
     * @test
     */
    public function emptyRequiredRolesAnnotationThrowsLogicException(): void
    {
        $authConstraint = new AuthConstraint(NewInstance::of(
            RoutingAnnotations::class, [function() {}])->returns([
                'requiredRole' => null
            ])
        );
        expect(function() use($authConstraint) {
          $authConstraint->satisfiedByRoles(new Roles(['admin']));
        })
            ->throws(\LogicException::class)
            ->withMessage('Route says it requires a role but doesn\'t specify which.');
    }
}