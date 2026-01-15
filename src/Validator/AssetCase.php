<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates case change for assets.
 * 
 * @author Ben Brooksnieder
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AssetCase extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $assignedToOtherCase = 'asset.case.assigned_to_other_case';
}
