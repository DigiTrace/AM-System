<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Form\Asset\ImageSourceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Action to store/save contents of target drive onto asset.
 *
 * @author Ben Brooksnieder
 */
class AddHddImage extends AssetAction
{
    protected string $name = 'asset.actions.add_hdd_image';

    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected State $newState = State::SavedImage;

    protected function action(Asset $asset, $data): array
    {
        $asset->addImage($data['image_source']);

        return [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('image_source', ImageSourceType::class, []);
    }

    // public function getConstraints(): array
    // {
    //     return [new \App\Validator\Asset\AssignedCase()];
    // }
}
