<?php

namespace App\Tests\Validator;

use App\Enum\AssetState as State;
use App\Tests\_support\AssertViolations;
use App\Tests\_support\TestHelper;
use App\Tests\Factory\AssetFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Ben Brooksnieder
 */
class AssetStateValidatorTest extends KernelTestCase
{
    use AssertViolations;

    private ?ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testFailAssetNotEditable(): void
    {
        $factory = AssetFactory::new();

        $uneditableStates = [State::Destroyed, State::Lost];

        foreach ($uneditableStates as $finalState) {
            $asset = $factory->with(['state' => $finalState])->create()->_real();
            foreach (State::cases() as $state) {
                if ($state == $finalState) {
                    continue;
                }

                $asset->setState($state);
                $violations = $this->validator->validateProperty($asset, 'state');
                $this->assertViolationsContainsMessage($violations, 'asset.state.uneditable');
            }
        }
    }

    public function testFailSameState(): void
    {
        $factory = AssetFactory::new();

        foreach (State::cases() as $state) {
            if (\in_array($state, State::getAllowedOverrideStates())) {
                continue;
            }
            $asset = $factory->with(['state' => $state])->create();
            $asset->_refresh();
            $asset = $asset->_real();
            $asset = $factory->find($asset)->_real();
            $asset->setState($state);
            $violations = $this->validator->validateProperty($asset, 'state');
            $this->assertViolationsContainsMessage($violations, 'asset.state.same_state');
        }
    }
}
