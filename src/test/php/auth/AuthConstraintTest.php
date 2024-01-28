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
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\webapp\routing\{RoutingAnnotations, UriResource};

use function bovigo\assert\{
    expect,
};
/**
 * Test for specific situation which shouldn't occur but must be taken care of in code.
 *
 * @since  8.0.0
 */
#[Group('auth')]
class AuthConstraintTest extends TestCase
{
    #[Test]
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
            ->throws(LogicException::class)
            ->withMessage('Route says it requires a role but doesn\'t specify which.');
    }
}