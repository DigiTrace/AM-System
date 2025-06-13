<?php

namespace App\Tests\_support;

/**
 * @author Ben Brooksnieder
 */
final class TestHelper
{

    /**
     * Call after something thats changes the DB state. Now the DB changes are actually persisted and you can debug them
     * @return never
     */
    public static function dieForDebug()
    {
        // ... something thats changes the DB state
        \DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver::commit();
        die;
        // now the DB changes are actually persisted and you can debug them
    }
}
