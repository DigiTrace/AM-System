<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        "@DoctrineAnnotation" => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
