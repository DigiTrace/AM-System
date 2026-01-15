<?php

namespace App\Tests;

use App\Enum\AssetCategory as Category;
use App\Validator\Barcode;
use App\Validator\BarcodeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertNotCount;

/**
 * @author Ben Brooksnieder
 */
class BarcodeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new BarcodeValidator();
    }

    protected function createContext(?ValidatorInterface $validator = null): ExecutionContextInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())->method('trans')->willReturnArgument(0);

        return new ExecutionContext($validator ?? Validation::createValidator(), $this->root, $translator);
    }

    public function validInputProvider(): \Generator
    {
        yield ['DTAS00000', Category::Exhibit];
        yield ['DTHW00001', Category::Equipment];
        yield ['DTHW00002', Category::Container];
        yield ['DTHD00003', Category::Hdd];
        yield ['DTAK00004', Category::Record];
        yield ['DTAS00005', Category::ExhibitHdd];
    }

    public function invalidInputCompoundProvider(): \Generator
    {
        yield [null];
        yield [''];
        yield ['short'];
        yield ['way_to_long'];
        yield ['DTDT12345'];
        yield ['DTDTDTDTD'];
        yield ['DTHWeeeee'];
    }

    public function invalidInputConstraintProvider(): \Generator
    {
        yield ['DTAS12345', Category::Hdd];
        yield ['DTHD12345', Category::ExhibitHdd];
        yield ['DTHW12345', Category::Exhibit];
        yield ['DTAK12345', Category::Container];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setObject(new class(Category::Exhibit) {
            public function __construct(public Category $category)
            {
            }

            public function getCategory(): Category
            {
                return $this->category;
            }
        });
    }

    /**
     * @dataProvider validInputProvider
     */
    public function testValidInput($value, Category $category): void
    {
        $constraint = new Barcode(options: []);
        $this->object->category = $category;

        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidInputCompoundProvider
     */
    public function testInvalidInputCompound($value): void
    {
        $constraint = new Barcode();
        $this->validator->validate($value, $constraint);
        assertNotCount(0, $this->context->getViolations());
    }

    /**
     * @dataProvider invalidInputConstraintProvider
     */
    public function testInvalidInputConstraint($value, Category $category): void
    {
        $constraint = new Barcode();
        $this->object->category = $category;
        $this->validator->validate($value, $constraint);
        assertCount(1, $this->context->getViolations());
    }
}
