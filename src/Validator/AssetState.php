<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that state change is valid.
 * 
 * @author Ben Brooksnieder
 * 
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AssetState extends Constraint
{
    #TODO
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value "{{ value }}" is not valid.';
    public $uneditableState = 'asset.state.uneditable';
    public $sameState = 'asset.state.same_state';
}
