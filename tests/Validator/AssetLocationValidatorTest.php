<?php

namespace App\Tests\Validator;

use App\Enum\AssetState;
use App\Tests\_support\AssertViolations;
use App\Tests\Factory\AssetFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Ben Brooksnieder
 */
class AssetLocationValidatorTest extends KernelTestCase
{
    use AssertViolations;
    use Factories;

    private ?ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testFailTargetNotStorage(): void
    {
        $factory = AssetFactory::new();

        $target = $factory->hdd()->create()->_real();
        $asset = $factory->with(['location' => null])->create();

        $asset = $asset->_real();
        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertViolationsContainsMessage($violations, 'asset.location.target_not_storage');
    }

    public function testFailTargetNotEditable(): void
    {
        $factory = AssetFactory::new();

        $target = $factory->container()->with(['state' => AssetState::Destroyed])->create()->_real();
        $asset = $factory->with(['location' => null])->create();

        $asset = $asset->_real();
        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertViolationsContainsMessage($violations, 'asset.location.target_not_editable');
    }

    public function testFailTargetSameLocation(): void
    {
        $factory = AssetFactory::new();

        $asset = $factory->hdd()->create()->_real();

        $asset->setLocation($asset);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertViolationsContainsMessage($violations, 'asset.location.same_location');
    }

    public function testFailCyclicReference1(): void
    {
        $factory = AssetFactory::new();
        $asset = $factory->container()->create()->_real();
        $target = $factory->container()->with(['location' => $asset])->create()->_real();

        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertViolationsContainsMessage($violations, 'asset.location.cyclic_reference');
    }

    public function testFailCyclicReference2(): void
    {
        $factory = AssetFactory::new();
        $asset = $factory->container()->create()->_real();
        $temp = $factory->container()->with(['location' => $asset])->create()->_real();
        $target = $factory->container()->with(['location' => $temp])->create()->_real();

        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertViolationsContainsMessage($violations, 'asset.location.cyclic_reference');
    }

    public function testSuccessNoPrevious(): void
    {
        $factory = AssetFactory::new();

        $target = $factory->container()->create()->_real();

        $asset = $factory->with(['location' => null])->create();
        $asset = $asset->_real();
        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertNoViolation($violations);
    }

    public function testSuccessChange(): void
    {
        $factory = AssetFactory::new();

        $target = $factory->container()->create()->_real();
        $prev = $factory->container()->create()->_real();

        $asset = $factory->with(['location' => $prev])->create();
        $asset = $asset->_real();
        $asset->setLocation($target);

        $violations = $this->validator->validateProperty($asset, 'location');
        $this->assertNoViolation($violations);
    }
}
