<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Clean extends AssetAction
{
    protected string $name = 'asset.actions.zero';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = false;
    protected ?State $newState = State::Cleaned;

    protected function action(Asset $asset, $data): array
    {
        $asset->flushImages();

        return [];
    }
}
