<?php


namespace App\Tests\_support;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertNotCount;
use function PHPUnit\Framework\assertTrue;

trait AssertViolations
{
    public function assertNoViolation(ConstraintViolationListInterface $violations) {
        assertCount(0, $violations);
    }
    
    public function assertViolationsContainsMessage(ConstraintViolationListInterface $violations, string $message, $debug = null) {
        // assertNotCount(0, $violations);
        $found = false;
        foreach ($violations as $value) {
            if ($value->getMessage() == $message) {
                $found = true;
                break;
            }
        }
        $debug ??= "Did not find '$message' in violations";
        assertTrue($found, $debug);
    }
}
