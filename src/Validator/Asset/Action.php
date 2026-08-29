<?php

namespace App\Validator\Asset;

use Symfony\Component\Validator\Constraint;

/**
 * Constraints for all asset actions after performing action.
 * 
 * @author Ben Brooksnieder
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Action extends Constraint
{
    public string $usageRequired = 'asset.action.usage_required';
}
