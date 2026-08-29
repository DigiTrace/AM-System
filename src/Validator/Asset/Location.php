<?php

namespace App\Validator\Asset;

use Symfony\Component\Validator\Constraint;

/**
 * Validates location/storage attribute for assets.
 * 
 * @author Ben Brooksnieder
 * 
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Location extends Constraint
{
    public $targetNull = "asset.location.target_null";
    public $targetNotStorage = "asset.location.target_not_storage";
    public $targetNotEditable = "asset.location.target_not_editable";
    public $sameLocation = "asset.location.same_location";
    public $cyclicReference = "asset.location.cyclic_reference";
}
