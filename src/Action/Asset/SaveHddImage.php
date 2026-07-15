<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use App\Form\Asset\ImageTargetType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Action to store/save contents of asset to target drive.
 *
 * @author Ben Brooksnieder
 */
class SaveHddImage extends BaseAction
{
    protected string $name = 'asset.actions.save_hdd_image';

    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::SavedImage;

    protected function doAction(Asset $asset, $data): array
    {
        $asset->addHdd($data['image_target']);

        return [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('image_target', ImageTargetType::class, []);
    }
}
