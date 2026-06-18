<?php

namespace App\Validator\Asset;

use App\Entity\Asset as Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
final class EditValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var AssetAction $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        /** @var Entity $asset */
        $asset = $value;
        $uow = $this->entityManager->getUnitOfWork();
        $original = $uow->getOriginalEntityData($asset);

        // check if name is equal
        if ($original['name'] != $asset->getName()) {
            return;
        }

        // check if note is equal
        if ($original['note'] != $asset->getNote()) {
            return;
        }

        // check if usage is equal
        if ($original['usage'] != $asset->getUsage()) {
            return;
        }

        // check if drive has changed
        if ($asset->isDrive()) {
            $uow->computeChangeSets();
            if ($uow->getEntityChangeSet($asset->getDrive())) {
                return;
            }
        }

        $this->context->buildViolation($constraint->noChangesMade)
            ->setParameter('{{ value }}', $value)
            ->addViolation()
        ;
    }
}
