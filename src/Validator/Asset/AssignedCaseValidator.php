<?php

namespace App\Validator\Asset;

use App\Entity\Asset as Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
class AssignedCaseValidator extends ConstraintValidator
{
    /**
     * @param AssignedCase $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }


        // TODO check for correct type of value
        /** @var Entity $asset */
        $asset = $value;
        $case = $asset->getCase();

        // check case is set
        if (!$case) {
            $this->context->buildViolation($constraint->caseNull)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }
    }
}
