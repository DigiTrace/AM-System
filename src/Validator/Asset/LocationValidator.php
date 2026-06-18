<?php

namespace App\Validator\Asset;

use App\Entity\Asset as Entity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
class LocationValidator extends ConstraintValidator
{
    /**
     * @param Location $constraint ´
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        // TODO check for correct type of value
        /** @var Entity $asset */
        $asset = $value;
        $location = $asset->getLocation();

        // check location is set
        if (!$location) {
            $this->context->buildViolation($constraint->targetNull)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // check if target is storage
        if (!$location->isStorage()) {
            $this->context->buildViolation($constraint->targetNotStorage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        // check if target is valid (aka != destroyed or lost)
        if (!$location->isEditable()) {
            $this->context->buildViolation($constraint->targetNotEditable)
                ->addViolation();
        }

        // check if target is itself
        if ($asset->getBarcode() == $location->getBarcode()) {
            $this->context->buildViolation($constraint->sameLocation)
               ->addViolation();
        }

        // check if target storage or storage`s storage is this object etc.
        $target = $location;
        while ($target = $target->getLocation()) {
            if ($asset->getBarcode() == $target->getBarcode()) {
                $this->context->buildViolation($constraint->cyclicReference)
                ->addViolation();
                break;
            }
        }
    }
}
