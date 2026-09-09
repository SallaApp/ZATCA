<?php


namespace Salla\ZATCA\Test\Unit;

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tag;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateQrCodeTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function shouldGenerateAQrCode()
    {
        $generatedString = GenerateQrCode::fromArray([
            new Tag(1, 'Salla'),
            new Tag(2, '1234567891'),
            new Tag(3, '2021-07-12T14:25:09Z'),
            new Tag(4, '100.00'),
            new Tag(5, '15.00')
        ])->toBase64();

        $this->assertEquals(
            'AQVTYWxsYQIKMTIzNDU2Nzg5MQMUMjAyMS0wNy0xMlQxNDoyNTowOVoEBjEwMC4wMAUFMTUuMDA=', $generatedString);
    }

    /** @test */
    public function shouldGenerateAQrCodeAsArabic()
    {
        $generatedString = GenerateQrCode::fromArray([
            new Tag(1, 'سلة'),
            new Tag(2, '1234567891'),
            new Tag(3, '2021-07-12T14:25:09Z'),
            new Tag(4, '100.00'),
            new Tag(5, '15.00')
        ])->toBase64();

        $this->assertEquals(
            'AQbYs9mE2KkCCjEyMzQ1Njc4OTEDFDIwMjEtMDctMTJUMTQ6MjU6MDlaBAYxMDAuMDAFBTE1LjAw', $generatedString);
    }

    /** @test */
    public function shouldGenerateAQrCodeFromTagsClasses()
    {
        $generatedString = GenerateQrCode::fromArray([
            new Seller('Salla'),
            new TaxNumber('1234567891'),
            new InvoiceDate('2021-07-12T14:25:09Z'),
            new InvoiceTotalAmount('100.00'),
            new InvoiceTaxAmount('15.00')
        ])->toBase64();

        $this->assertEquals(
            'AQVTYWxsYQIKMTIzNDU2Nzg5MQMUMjAyMS0wNy0xMlQxNDoyNTowOVoEBjEwMC4wMAUFMTUuMDA=', $generatedString);
    }

    /** @test */
    public function shouldGenerateAQrCodeDisplayAsImageData()
    {
        $generatedString = GenerateQrCode::fromArray([
            new Seller('Salla'),
            new TaxNumber('1234567891'),
            new InvoiceDate('2021-07-12T14:25:09Z'),
            new InvoiceTotalAmount('100.00'),
            new InvoiceTaxAmount('15.00')
        ])->render();

        $this->assertEquals(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAM0AAADNCAIAAACU3mM+AAAABnRSTlMA/wD/AP83WBt9AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAEkUlEQVR4nO3dy27kKABA0cmo//+X03svkBBwTVWfs03qkdQVsssYfn5/f/+Dw/5/+w3wT9AZBZ1R0BkFnVHQGQWdUdAZBZ1R0BkFnVHQGQWdUdAZBZ1R0BkFnVHQGQWdUfiz8uCfn59d72PscRPD43XHP82eeeVtjJ9q7K1PYYrxjILOKOiMgs4oLJ0HPGy85XjqiHvjC2185pVj+ZU/P/sUphjPKOiMgs4o6IzCzvOAh5Xv5aee+fHYjQfgG5955eB95Xj83KcwxXhGQWcUdEZBZxQOngdkpo64p2b+rFwt2DhN6AsYzyjojILOKOiMwjecB4yND8BXpvxvPC34+pMG4xkFnVHQGQWdUTh4HnBuksnK7bsPb025mZomlN1yfI7xjILOKOiMgs4o7DwPyL7Fnvoy/c61hjZeaXi481qC8YyCzijojILOKPxc8n3xio2Lfa4887mD9y/4jIxnFHRGQWcUdEZh6Tzg3HyVSw7Ax1be1covj9/G1GPHT7Xx/2w8o6AzCjqjoDMKt1wPODddZ6NL3uS5iwfndlMwnlHQGQWdUdAZhZ3XA+7c3iu7aLHy2Gwu09i5MwzjGQWdUdAZBZ1R2Hk94K3bdzce6k7ZeJaQXWnIJlw9GM8o6IyCzijojMJr84I2HpB+4hJA505HLpm89GA8o6AzCjqjoDMKl94nPPW62aHuua/Lxy/08NbFEvOCuJ3OKOiMgs4o7Nw/4Nz8nHNr70wd+V4yXWfqhS65xdp4RkFnFHRGQWcUDu4nPD62XTnkfOsCwMpTTT3zxhda+V9ZN5QPozMKOqOgMwoH1ws6t7FtNtd+ysbJPBud211givGMgs4o6IyCzigcvB6QbaS10bntvc4tHzT+6dRfZF4Qn01nFHRGQWcUls4DLlnd89yX+NmR/viXp9y5IYTxjILOKOiMgs4oHFwv6Av2xtq4INIlS5Ce2xltzHhGQWcUdEZBZxRe20dsysbtrrKF+6cee85ba7s+GM8o6IyCzijojEJ3PWCjS9YcnbJxo4KNs3eyT9B4RkFnFHRGQWcUDu4nfMlMmIe3/t6pxz5kV1amHjvFeEZBZxR0RkFnFHauF/TW+p2XnEOsfGuf3UWcbYn8YDyjoDMKOqOgMwoH9w8Y//LDJVsCZPfc3sm6oXw2nVHQGQWdUTg4L+icc+vnvHUt4dwNAef+G1OMZxR0RkFnFHRG4eB9whu9tZ/XlEvm+Gd/7xTjGQWdUdAZBZ1R2Hl/wLklSDe+jY/4Xn7KR9wuYDyjoDMKOqOgMwo7zwMe7lxPf+zcxJiV1314a3rSCuMZBZ1R0BkFnVE4eB5wztRhcrb77sYJOee2Yn6L8YyCzijojILOKHzkecDDytfW4yPuc7P4N26fMP7lcwsxTTGeUdAZBZ1R0BmFg+cBb031WZncsnLUnN2Ru/G0YMx6QXwYnVHQGQWdUbh0H7GV171zys3Gs4S3TjhWGM8o6IyCzijojMJH7h/AxzGeUdAZBZ1R0BkFnVHQGQWdUdAZBZ1R0BkFnVHQGQWdUdAZBZ1R0BkFnVHQGQWdUfgLoOr+eP/ycgUAAAAASUVORK5CYII=', $generatedString);
    }

    /**
     * @test
     */
    public function shouldThrowExpectionWithWrongData()
    {
        $this->expectException(\InvalidArgumentException::class);

        GenerateQrCode::fromArray([null])->toBase64();
    }

    /**
     * A 128 character Arabic seller name is 256 bytes in UTF-8, which does not
     * fit in the single length byte ZATCA allows.
     *
     * @test
     */
    public function shouldThrowWhenTheValueIsTooLongForTheLengthByte()
    {
        $this->expectException(\InvalidArgumentException::class);

        (string) new Tag(1, str_repeat('a', 256));
    }

    /**
     * @test
     */
    public function shouldEncodeTheLengthInASingleByteUpToTheLimit()
    {
        foreach ([0, 1, 127, 128, 200, 255] as $length) {
            $tag = (string) new Tag(1, str_repeat('a', $length));

            $this->assertEquals($length + 2, strlen($tag));
            $this->assertEquals($length, ord($tag[1]));
        }
    }

    /**
     * A 138 byte Arabic trade name has 0x8A as its length byte. That is a
     * plain unsigned 8-bit 138, not a BER multi byte prefix, so it round trips.
     *
     * @test
     */
    public function shouldEncodeALongArabicSellerName()
    {
        $name = 'مؤسسة التقنية المتقدمة للتجارة والمقاولات العامة بالمنطقة الوسطى المحدودة';

        $this->assertEquals(138, strlen($name));

        $tag = (string) new Tag(1, $name);

        $this->assertEquals(0x8A, ord($tag[1]));
        $this->assertEquals($name, substr($tag, 2));
    }

    /**
     * The tag id and the value length are both written as one byte, so the
     * error has to say which of the two was out of range.
     *
     * @test
     */
    public function shouldReportWhichByteWasOutOfRange()
    {
        try {
            (string) new Tag(256, 'a');
            $this->fail('an out of range tag id should throw');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Tag id 256 is out of range', $e->getMessage());
        }

        try {
            (string) new Tag(1, str_repeat('a', 256));
            $this->fail('an oversized value should throw');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('the value is 256 bytes', $e->getMessage());
        }
    }

    /**
     * getLength() must measure whatever __toString() actually writes. A
     * subclass that overrides getValue() previously made the declared length
     * disagree with the bytes emitted, which misaligns every following tag.
     *
     * @test
     */
    public function shouldMeasureTheValueItActuallyWrites()
    {
        $tag = new class(1, '  hi  ') extends Tag
        {
            public function getValue()
            {
                return trim(parent::getValue());
            }
        };

        $encoded = (string) $tag;

        $this->assertEquals(2, ord($encoded[1]));
        $this->assertEquals('hi', substr($encoded, 2));
        $this->assertEquals(strlen($encoded) - 2, ord($encoded[1]));
    }

    /**
     * __toString() must measure the exact string it writes. A getValue() that
     * returns something different on each call previously had its length taken
     * from a second call, so the declared length described one string while
     * another was emitted, misaligning every following tag.
     *
     * @test
     */
    public function shouldMeasureTheSameCallItWrites()
    {
        $tag = new class(1, 'ignored') extends Tag
        {
            private $calls = 0;

            public function getValue()
            {
                // longer on the first call, shorter on the next
                return str_repeat('x', 10 - (2 * $this->calls++));
            }
        };

        $encoded = (string) $tag;
        $declared = ord($encoded[1]);
        $written = strlen($encoded) - 2;

        $this->assertEquals($written, $declared, 'the length byte must match the bytes emitted');
    }
}
