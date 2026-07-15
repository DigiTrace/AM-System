<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class UnbindReservation extends BaseAction
{
    protected string $name = 'asset.actions.unreserve';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = false;
    protected ?State $newState = State::UnbindReservation;

    public function action(Asset $asset, $data): ?array
    {
        if (null === $asset->getReservedBy()) {
            return null;
        }

        $asset->setReservedBy(null);

        return parent::action($asset, $data);
    }
}
