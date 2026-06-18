<?php

namespace App\Action\Asset;

use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Lost extends AssetAction
{
    protected string $name = 'asset.actions.lost';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = true;
    protected bool $usageRequired = false;
    protected State $newState = State::Lost;
    protected array $messages = [
        ['info', 'asset.action.lost.info'],
        ['warning', 'asset.action.lost.warning'],
    ];
}
