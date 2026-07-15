<?php

namespace App\Action\Asset;

use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Handover extends BaseAction
{
    protected string $name = 'asset.actions.handover';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::HandoverPerson;
    protected array $messages = [
        ['info', 'asset.action.handover.info'],
    ];
}
