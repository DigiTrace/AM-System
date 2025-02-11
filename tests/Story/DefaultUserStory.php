<?php

namespace App\Tests\Story;

use App\Tests\Factory\NutzerFactory;
use Zenstruck\Foundry\Story;

/**
 * Create admin user `admin:test` and regular user `user:test`. 
 * 
 * @author Ben Brooksnieder 
 */
final class DefaultUserStory extends Story
{
    public function build(): void
    {
        $factory = NutzerFactory::new();
        $factory->enabled()->testPassword()->admin()->with([
            'username' => 'admin',
            'fullname' => 'admin',
            'email' => 'admin@localhost',
        ])->create();
        
        $factory->enabled()->testPassword()->with([
            'username' => 'user',
            'fullname' => 'user',
            'email' => 'user@localhost',
        ])->create();
    }
}
