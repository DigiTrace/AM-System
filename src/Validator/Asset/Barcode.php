<?php

namespace App\Validator\Asset;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Validator for DT barcodes. Also checks category,
 * thus entity object with `getCategory` is required for validation.
 *
 * @author Ben Brooksnieder
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Barcode extends Compound
{

    #[HasNamedArguments()]
    public function __construct(
        public $value = null,
        public ?string $propertyPath = null,
        mixed $options = null, 
        ?array $groups = null, 
        mixed $payload = null
    ){
        parent::__construct($options, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(allowNull: false),
            new Assert\Type('string'),
            new Assert\Length(exactly: 9),
            new Assert\Regex(pattern: '/^DT(AS|HD|HW|AK)\d{5}$/', message: 'Barcode format "DT(AS|HD|HW|AK)XXXXX" required'),
            new BarcodeCategory(value: $this->value, propertyPath: $this->propertyPath),
        ];
    }
}
