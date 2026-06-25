<?php declare(strict_types=1);

namespace Mautic\NotificationBundle\Exception;

class MissingAppIDException extends \Exception
{
    protected $message = 'Missing Notification App ID';
}
