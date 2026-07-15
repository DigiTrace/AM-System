<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class UnassignCase extends BaseAction
{
    protected string $name = 'asset.actions.unassign_case';
    protected bool $isSystemAction = false;
    protected bool $confirmationRequired = false;
    protected bool $usageRequired = true;
    protected ?State $newState = State::RemovedFromCase;
    protected array $messages = [
        ['info', 'asset.action.unassing_case.info'],
    ];

    public function action(Asset $asset, $data): ?array
    {
        if (null === $asset->getCase()) {
            return null;
        }

        $asset->setCase(null);

        return parent::action($asset, $data);
    }
}
