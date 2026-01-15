<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that asset with all attributes is valid. 
 * For single attributes refer to Asset*Validator.
 * 
 * @see AssetCase::class
 * @see AssetImage::class
 * @see AssetLocation::class
 * @see AssetState::class
 * @see Barcode::class
 * 
 * @author Ben Brooksnieder
 * 
 * @Annotation
 *
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Asset extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value "{{ value }}" is not valid.';
}
