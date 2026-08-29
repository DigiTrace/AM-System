<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Base type for asset list selections from dynamically queried tables.
 *
 * @author Ben Brooksnieder
 */
class AssetListType extends AbstractType
{
    public function getParent(): string
    {
        return ListType::class;
    }
}
