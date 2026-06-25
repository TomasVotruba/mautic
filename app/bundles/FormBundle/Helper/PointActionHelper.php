<?php declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

class PointActionHelper
{
    public static function validateFormSubmit($eventDetails, $action): bool
    {
        $form         = $eventDetails->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        if (!empty($limitToForms) && !in_array($formId, $limitToForms, true)) {
            // no points change
            return false;
        }

        return true;
    }
}
