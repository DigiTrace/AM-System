<?php

namespace App\Tests\Validator;

use App\Enum\AssetState;
use App\Tests\_support\AssertViolations;
use App\Tests\Factory\AssetFactory;
use App\Tests\Factory\FallFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Ben Brooksnieder
 */
class AssetCaseValidatorTest extends KernelTestCase
{
    use AssertViolations;

    private ?ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testFailAlreadyCaseAssigned(): void
    {
        $factory = AssetFactory::new();
        $caseFactory = FallFactory::new();

        $case = $caseFactory->active()->create()->_real();
        $newCase = $caseFactory->active()->create()->_real();

        $asset = $factory->with(['case' => $case])->create();
        $asset = $asset->_real();
        $asset->setCase($newCase);

        $violations = $this->validator->validateProperty($asset, 'case');
        $this->assertViolationsContainsMessage($violations, 'asset.case.assigned_to_other_case');
    }

    public function testSuccessNoPrevious(): void
    {
        $factory = AssetFactory::new();
        $caseFactory = FallFactory::new();

        $case = $caseFactory->active()->create()->_real();

        $asset = $factory->with(['case' => null])->create();
        $asset = $asset->_real();
        $asset->setCase($case);

        $violations = $this->validator->validateProperty($asset, 'case');
        $this->assertNoViolation($violations);
    }

    public function testSuccessSetToNull(): void
    {
        $factory = AssetFactory::new();
        $caseFactory = FallFactory::new();

        $case = $caseFactory->active()->create()->_real();

        $asset = $factory->with(['case' => $case])->create();
        $asset = $asset->_real();
        $asset->setCase(null);

        $violations = $this->validator->validateProperty($asset, 'case');
        $this->assertNoViolation($violations);
    }
}
