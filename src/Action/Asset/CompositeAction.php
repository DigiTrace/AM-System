<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Ben Brooksnieder
 */
abstract class CompositeAction implements ActionInterface
{
    protected string $name;
    protected bool $confirmationRequired;
    protected bool $usageRequired;
    protected array $messages = [];
    protected array $constraints = [];

    protected array $actions = [];

    /**
     * Get all composite children.
     *
     * @return array<string>
     */
    abstract protected function getCompounds(): array;

    public function __construct()
    {
        $classes = $this->getCompounds();
        foreach ($classes as $class) {
            $this->actions[] = new $class();
        }

        assert(\count($this->actions) > 0);
    }

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

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getActions(): \Iterator
    {
        return new \ArrayIterator($this->actions);
    }

    public function preValidation(ValidatorInterface $validator, Asset $asset)
    {
        return new ConstraintViolationList();
    }

    public function getNewState(): ?\App\Enum\AssetState
    {
        return $this->actions[\count($this->actions) - 1]->getNewState();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }
}
