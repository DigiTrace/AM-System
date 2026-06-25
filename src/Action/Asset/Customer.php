<?php

namespace App\Action\Asset;

use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Customer extends AssetAction
{
    protected string $name = 'asset.actions.customer';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::TakenToCustomer;
}
