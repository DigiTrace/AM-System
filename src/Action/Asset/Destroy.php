<?php

namespace App\Action\Asset;

use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Destroy extends AssetAction
{
    protected string $name = 'asset.actions.destroy';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = true;
    protected bool $usageRequired = true;
    protected State $newState = State::Destroyed;
    protected array $messages = [
        ['warning', 'asset.action.destroy.warning'],
    ];
}
