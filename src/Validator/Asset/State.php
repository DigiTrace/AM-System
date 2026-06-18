<?php

namespace App\Validator\Asset;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that state change is valid. Used for pre validation on asset actions.
 * 
 * @author Ben Brooksnieder
 * 
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class State extends Constraint
{
    public $uneditableState = 'asset.state.uneditable';
    public $sameState = 'asset.state.same_state';
}
