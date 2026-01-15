<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Validator for DT barcodes. Also checks category,
 * thus entity object with `getCategory` is required for validation.
 *
 * @author Ben Brooksnieder
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Barcode extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(allowNull: false),
            new Assert\Type('string'),
            new Assert\Length(exactly: 9),
            new Assert\Regex(pattern: '/^DT(AS|HD|HW|AK)\d{5}$/', message: 'Barcode format "DT(AS|HD|HW|AK)XXXXX" required'),
            new BarcodeCategory(value: $options['payload']['value'] ?? null, propertyPath: $options['payload']['propertyPath'] ?? null),
        ];
    }
}
