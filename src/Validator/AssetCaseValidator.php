<?php

namespace App\Validator;

use App\Entity\Asset as Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Ben Brooksnieder
 */
class AssetCaseValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param AssetCase $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        // get old entity
        /** @var Entity $object */
        $object = $this->context->getObject();
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $original = $unitOfWork->getOriginalEntityData($object);

        if (empty($original)) {
            return;
        } 

        // check if case property is already set
        
        if (!empty($original['fall_id']) &&
            $original['fall_id'] !== $value->getId() )
        {
            $this->context->buildViolation($constraint->assignedToOtherCase)
            ->setParameter('{{ case }}', $original['fall_id'])
            ->addViolation();
        }
    }
}
