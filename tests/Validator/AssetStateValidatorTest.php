<?php

namespace App\Tests\Validator;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Tests\_support\AssertViolations;
use App\Tests\_support\TestHelper;
use App\Tests\Factory\AssetFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Ben Brooksnieder
 */
class AssetStateValidatorTest extends KernelTestCase
{
    use AssertViolations;
    use Factories;

    private ?ValidatorInterface $validator;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFailAssetNotEditable(): void
    {
        $factory = AssetFactory::new();
        $repo = $this->entityManager->getRepository(Asset::class);

        $uneditableStates = [State::Destroyed, State::Lost];

        foreach ($uneditableStates as $finalState) {
            $proxy = $factory->with(['state' => $finalState])->create()->_real();
            foreach (State::cases() as $state) {
                if (\in_array($state, $uneditableStates)) {
                    continue;
                }

                $asset = $repo->find($proxy->getBarcode());

                $asset->setState($state);
                $violations = $this->validator->validateProperty($asset, 'state');
                $this->assertViolationsContainsMessage($violations, 'asset.state.uneditable', "bad state: " . $state->name);
            }
        }
    }

    public function testFailSameState(): void
    {
        $factory = AssetFactory::new();
        $repo = $this->entityManager->getRepository(Asset::class);

        foreach (State::cases() as $state) {
            if (\in_array($state, State::getAllowedOverrideStates())) {
                continue;
            }
            if (!\in_array($state, State::getEditableStates())) {
                continue;
            }
            $proxy = $factory->with(['state' => $state])->create();
            
            $asset = $repo->find($proxy->getBarcode());
            $asset->setState($state);
            $violations = $this->validator->validateProperty($asset, 'state');
            $this->assertViolationsContainsMessage($violations, 'asset.state.same_state');
        }
    }
}
