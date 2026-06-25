<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Form\Asset\AssignCaseType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Ben Brooksnieder
 */
class AssignCase extends AssetAction
{
    protected string $name = 'asset.actions.assign_case';

    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::AssignedCase;

    protected function action(Asset $asset, $data): array
    {
        $asset->setCase($data['case']);
        return [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('case', AssignCaseType::class, []);
    }

    public function getConstraints(): array
    {
        return [new \App\Validator\Asset\AssignedCase()];
    }
}
