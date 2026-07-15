<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class Reserve extends BaseAction
{
    protected string $name = 'asset.actions.reserve';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = false;
    protected ?State $newState = State::Reserved;

    public function action(Asset $asset, $data): ?array
    {
        $asset->setReservedBy($data['user']);

        return parent::action($asset, $data);
    }
}
