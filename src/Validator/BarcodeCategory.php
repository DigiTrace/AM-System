<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Ben Brooksnieder
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BarcodeCategory extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'Invalid barcode "{{ value }}" for category "{{ category }}".';
    public mixed $value = null;
    public ?string $propertyPath = null;

    public function __construct(mixed $value = null, ?string $propertyPath = null, ?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->value = $value;
        $this->propertyPath = $propertyPath;

        if (null === $this->value && null === $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires either the "value" or "propertyPath" option to be set.', static::class));
        }

        if (null !== $this->value && null !== $this->propertyPath) {
            throw new ConstraintDefinitionException(\sprintf('The "%s" constraint requires only one of the "value" or "propertyPath" options to be set, not both.', static::class));
        }

        if (null !== $this->propertyPath && !class_exists(PropertyAccess::class)) {
            throw new LogicException(\sprintf('The "%s" constraint requires the Symfony PropertyAccess component to use the "propertyPath" option. Try running "composer require symfony/property-access".', static::class));
        }
    }
}
