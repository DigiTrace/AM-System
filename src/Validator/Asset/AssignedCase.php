<?php

namespace App\Validator\Asset;

use Symfony\Component\Validator\Constraint;

/**
 * Validates case change for assign case asset action.
 * 
 * @author Ben Brooksnieder
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AssignedCase extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $caseNull = 'asset.case.case_null';
    public $assignedToOtherCase = 'asset.case.assigned_to_other_case';
}
