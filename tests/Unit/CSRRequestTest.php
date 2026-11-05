<?php

namespace Salla\ZATCA\Test\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Salla\ZATCA\Exception\CSRValidationException;
use Salla\ZATCA\Models\CSRRequest;

class CSRRequestTest extends TestCase
{
    /** A VAT group's number: its 11th digit is `1`. */
    private const GROUP_UID = '300000000010003';

    /** A single taxpayer's number: its 11th digit is not `1`. */
    private const TAXPAYER_UID = '311111111101113';

    public static function invalidUIDs(): array
    {
        return [
            'letters'           => ['3ABCDEFGHIJKLM3'],
            'spaces'            => ['3 1111111110111 3'],
            'dashes'            => ['3-111111110-113'],
            'too short'         => ['31111111110113'],
            'not starting 3'    => ['111111111101113'],
            'not ending with 3' => ['311111111101111'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUIDs')]
    public function shouldRejectAUIDThatIsNot15DigitsStartingAndEndingWith3(string $UID)
    {
        $this->expectException(CSRValidationException::class);

        CSRRequest::make()->setUID($UID);
    }

    #[Test]
    public function shouldAcceptAValidUID()
    {
        $this->assertSame(self::TAXPAYER_UID, CSRRequest::make()->setUID(self::TAXPAYER_UID)->getUID());
    }

    public static function invalidGroupMemberTins(): array
    {
        return [
            'too short' => ['331111111'],
            'too long'  => ['33111111111'],
            'a name'    => ['Riyadh Branch'],
            'letters'   => ['33111111AB'],
        ];
    }

    #[Test]
    #[DataProvider('invalidGroupMemberTins')]
    public function shouldRequireA10DigitTinForAVatGroup(string $organizationalUnitName)
    {
        $this->expectException(CSRValidationException::class);

        CSRRequest::make()->setUID(self::GROUP_UID)->setOrganizationalUnitName($organizationalUnitName);
    }

    #[Test]
    #[DataProvider('invalidGroupMemberTins')]
    public function shouldRequireA10DigitTinForAVatGroupWhenTheUIDIsSetLast(string $organizationalUnitName)
    {
        $this->expectException(CSRValidationException::class);

        CSRRequest::make()->setOrganizationalUnitName($organizationalUnitName)->setUID(self::GROUP_UID);
    }

    #[Test]
    public function shouldAcceptA10DigitTinForAVatGroup()
    {
        $request = CSRRequest::make()->setUID(self::GROUP_UID)->setOrganizationalUnitName('3311111111');

        $this->assertSame('3311111111', $request->getOrganizationalUnitName());
    }

    #[Test]
    public function shouldAcceptAnyOrganizationalUnitNameOutsideAVatGroup()
    {
        $request = CSRRequest::make()->setUID(self::TAXPAYER_UID)->setOrganizationalUnitName('Riyadh Branch');

        $this->assertSame('Riyadh Branch', $request->getOrganizationalUnitName());
    }

    #[Test]
    public function shouldAcceptTheOrganizationalUnitNameBeforeTheUID()
    {
        $request = CSRRequest::make()->setOrganizationalUnitName('Riyadh Branch')->setUID(self::TAXPAYER_UID);

        $this->assertSame('Riyadh Branch', $request->getOrganizationalUnitName());
    }
}
