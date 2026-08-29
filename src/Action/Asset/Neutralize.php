<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetCategory;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Ben Brooksnieder
 */
class Neutralize extends CompositeAction
{
    protected string $name = 'asset.actions.neutralize';
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = false;
    protected array $messages = [
        ['info', 'asset.action.neutralize.info'],
    ];

    protected function getCompounds(): array
    {
        return [
            UnassignCase::class,
            PullOutOfContainer::class,
            Clean::class,
        ];
    }

    public function preValidation(ValidatorInterface $validator, Asset $asset)
    {
        return $validator->validate($asset->getCategory(), new EqualTo(AssetCategory::Hdd));
    }
}
