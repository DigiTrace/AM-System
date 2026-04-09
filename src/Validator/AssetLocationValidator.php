<?php

namespace App\Validator;

use App\Entity\Asset as Entity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
class AssetLocationValidator extends ConstraintValidator
{
    /**
     * @param AssetLocation $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        /** @var Entity $object */
        $object = $this->context->getObject();

        // check if target is storage
        if (!$value->isStorage()) {
            $this->context->buildViolation($constraint->targetNotStorage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        // check if target is valid (aka != destroyed or lost)
        if (!$value->isEditable()) {
            $this->context->buildViolation($constraint->targetNotEditable)
                ->addViolation();
        }

        // check if target is itself
        if ($value->getBarcode() == $object->getBarcode()) {
            $this->context->buildViolation($constraint->sameLocation)
               ->addViolation();
        }

        // check if target storage or storage`s storage is this object etc.
        $target = $value->getLocation();
        while (null != $target) {
            if ($object->getBarcode() == $target->getBarcode()) {
                $this->context->buildViolation($constraint->cyclicReference)
                ->addViolation();
                break;
            }
            $target = $target->getLocation();
        }
    }
}
