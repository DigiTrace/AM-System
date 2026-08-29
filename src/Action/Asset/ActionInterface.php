<?php

namespace App\Action\Asset;

use App\Entity\Asset;
use App\Enum\AssetState as State;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Ben Brooksnieder
 */
interface ActionInterface
{
    public function getName(): string;

    public function isConfirmationRequired(): bool;

    public function isUsageRequired(): bool;

    public function getNewState(): ?State;

    public function getMessages(): array;

    public function preValidation(ValidatorInterface $validator, Asset $asset);

    /**
     * Callback used to modify forms.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void;

    /**
     * @return \Iterator<BaseAction>
     */
    public function getActions(): \Iterator;
}
