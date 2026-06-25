<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base class for asset actions.
 *
 * @author Ben Brooksnieder
 */
abstract class AssetAction
{
    protected string $name;
    protected bool $isSystemAction;
    protected bool $confirmationRequired;
    protected bool $usageRequired;
    protected ?State $newState;
    protected array $messages = [];
    protected ?array $selector = null;

    protected array $constraints = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function isConfirmationRequired(): bool
    {
        return $this->confirmationRequired;
    }

    public function isUsageRequired(): bool
    {
        return $this->usageRequired;
    }

    public function getNewState(): ?State
    {
        return $this->newState;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function preValidation(ValidatorInterface $validator, Asset $asset)
    {
        return $validator->validate([$asset, $this], new \App\Validator\Asset\State());
    }

    final public function performAction(Asset $asset, $data): array
    {
        $time = new \DateTime();

        if (!empty($data['usage']) || $this->usageRequired) {
            $asset->setUsage($data['usage']);
        }

        $asset->setState($this->newState);
        $asset->setSystemAction($this->isSystemAction);
        $asset->setModifiedBy($data['user']);
        $asset->setLastUpdatedOn($time);
        $asset->setLastUpdatePerformedOn($data['lastUpdatePerformedOn']);

        return $this->action($asset, $data);
    }

    /**
     * Get constraints to be validated after action.
     *
     * @return Constraint[]
     */
    final public function getFinalConstraints(): array
    {
        return \array_merge([], $this->getConstraints());
    }

    protected function getConstraints(): array
    {
        return [];
    }

    protected function action(Asset $asset, $data): array
    {
        return [];
    }

    /**
     * Callback used to modify forms.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }
}
