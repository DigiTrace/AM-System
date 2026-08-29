<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use ArrayIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base class for asset actions.
 *
 * @author Ben Brooksnieder
 */
abstract class BaseAction implements ActionInterface
{
    protected string $name;
    protected bool $confirmationRequired;
    protected bool $usageRequired;
    protected ?State $newState;
    protected array $messages = [];

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

    final public function getConstraints(): array
    {
        return \array_merge([], $this->prepareConstraints());
    }

    protected function prepareConstraints(): array
    {
        return [];
    }

    public function action(Asset $asset, $data): ?array
    {
        $time = new \DateTime();

        if (!empty($data['usage']) || $this->usageRequired) {
            $asset->setUsage($data['usage']);
        }

        $asset->setState($this->newState);
        $asset->setModifiedBy($data['user']);
        $asset->setLastUpdatedOn($time);
        $asset->setLastUpdatePerformedOn($data['lastUpdatePerformedOn']);

        return [];
    }

    /**
     * Callback used to modify forms.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function getActions(): \Iterator {
        return new ArrayIterator([$this]);
    }
}
