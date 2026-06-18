<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Neutralize extends AssetAction
{
    protected string $name = 'asset.actions.neutralize';

    protected bool $isSystemAction = true;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected State $newState = State::Cleaned;
    protected array $messages = [
        ['info', 'asset.action.neutralize.info'],
    ];

    protected function action(Asset $asset, $data): array
    {
        $asset->setCase(null);
        $asset->setLocation(null);

        return [];
    }
}
