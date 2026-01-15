<?php

namespace App\Validator;

use App\Enum\AssetCategory as Category;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @author Ben Brooksnieder
 */
class BarcodeCategoryValidator extends ConstraintValidator
{
    public function __construct(private ?PropertyAccessorInterface $propertyAccessor = null)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var App\Validator\BarcodeCategory $constraint */

        if (null === $value || '' === $value) {
            return;
        }

         if ($path = $constraint->propertyPath) {
            if (null === $object = $this->context->getObject()) {
                return;
            }

            try {
                $category = $this->getPropertyAccessor()->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, get_debug_type($constraint)).$e->getMessage(), 0, $e);
            } catch (UninitializedPropertyException) {
                $category = null;
            }
        } else {
            $category = $constraint->value;
        }

        if (!($category instanceof Category)) {
            throw new ConstraintDefinitionException(\sprintf('Invalid type for value provided "%s", must be of type "%s"', $path, get_debug_type($category), Category::class));
        }

        $prefix = \substr($value, 0, 4);
        $violation = $this->context->buildViolation($constraint->message);
        $violation->setParameter('{{ category }}', $category->name);
        if ('DTAS' == $prefix && !\in_array($category, [Category::Exhibit, Category::ExhibitHdd])) {
            $violation->setParameter('{{ value }}', $value);
        } elseif ('DTHD' == $prefix && !\in_array($category, [Category::Hdd])) {
            $violation->setParameter('{{ value }}', $value);
        } elseif ('DTHW' == $prefix && !\in_array($category, [Category::Equipment, Category::Container])) {
            $violation->setParameter('{{ value }}', $value);
        } elseif ('DTAK' == $prefix && !\in_array($category, [Category::Record])) {
            $violation->setParameter('{{ value }}', $value);
        } else {
            // no violation
            return;
        }
        $violation->addViolation();
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }
}
