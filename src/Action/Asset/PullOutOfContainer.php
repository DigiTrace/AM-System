<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class PullOutOfContainer extends BaseAction
{
    protected string $name = 'asset.actions.remove_container';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::PulledOutOfContainer;
    protected array $messages = [
        ['info', 'asset.action.remove_container.info'],
    ];

    public function action(Asset $asset, $data): ?array
    {
        if ($asset->getLocation() === null) {
            return null;
        }
        
        $asset->setLocation(null);

        return parent::action($asset, $data);
    }
}
