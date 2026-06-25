<?php declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

class MessageOnlyErrorHandlerException extends ErrorHandlerException
{
    public function __construct($message = '')
    {
        parent::__construct($message, true);
    }
}
