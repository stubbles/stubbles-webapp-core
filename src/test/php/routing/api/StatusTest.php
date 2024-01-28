<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Binder;
use stubbles\xml\serializer\XmlSerializerFacade;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\routing\api\Status.
 *
 * @since  6.1.0
 */
#[Group('routing')]
#[Group('routing_api')]
class StatusTest extends TestCase
{
    private Status $status;

    protected function setUp(): void
    {
        $this->status = new Status(200, 'Default <b>response</b> code');
    }

    #[Test]
    public function returnsProvidedStatusCode(): void
    {
        assertThat($this->status->code(), equals(200));
    }

    #[Test]
    public function returnsProvidedDescription(): void
    {
        assertThat($this->status->description(), equals('Default <b>response</b> code'));
    }

    #[Test]
    public function canBeSerializedToJson(): void
    {
        assertThat(
            json_encode($this->status),
            equals('{"code":200,"description":"Default <b>response<\/b> code"}')
        );
    }

    #[Test]
    public function canBeSerializedToXml(): void
    {
        /**  @var  XmlSerializerFacade  $xmlSerializer */
        $xmlSerializer = (new Binder())->getInjector()->getInstance(XmlSerializerFacade::class);
        assertThat(
            $xmlSerializer->serializeToXml($this->status),
            equals(
                '<?xml version="1.0" encoding="UTF-8"?>
<status code="200"><description>Default <b>response</b> code</description></status>'
            )
        );
    }
}
