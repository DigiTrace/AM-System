<?php

namespace App\Action\Asset;

use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Used extends AssetAction {
    protected string $name = "asset.actions.used";
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::Used;
}