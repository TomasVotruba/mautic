<?php declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Stubs;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class StubIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'Stub';
    }
}
