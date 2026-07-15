<?php

namespace App\Validator\Asset;

use App\Entity\Asset as Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
final class ActionValidator extends ConstraintValidator
{

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }


    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var BaseAction $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        /** @var Entity $asset */
        $asset = $value;

        // check usage is set
        if (empty($asset->getUsage())) {
            $this->context->buildViolation($constraint->usageRequired)
                ->setParameter('{{ value }}', $value)
                ->addViolation()
            ;
        }

        // TODO check update performed on
        // $uow = $this->entityManager->getUnitOfWork();
        // $original = $uow->getOriginalEntityData($asset);
        // if ($asset->getLast)
    }
}
