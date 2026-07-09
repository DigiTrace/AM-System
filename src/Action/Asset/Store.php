<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Form\Asset\StorageType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Ben Brooksnieder
 */
class Store extends AssetAction
{
    protected string $name = 'asset.actions.store';

    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::StoredInContainer;

    protected function action(Asset $asset, $data): array
    {
        $asset->setLocation($data['location']);

        return [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('location', StorageType::class, [
            'validation_groups' => false,
        ]);
    }

    public function getConstraints(): array
    {
        return [new \App\Validator\Asset\Location()];
    }
}
