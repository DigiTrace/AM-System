<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;


    # set Timezone ,see https://lindevs.com/set-default-time-zone-in-symfony
    public function boot(): void
    {
        parent::boot();
        date_default_timezone_set($this->getContainer()->getParameter('timezone'));
    }
}
