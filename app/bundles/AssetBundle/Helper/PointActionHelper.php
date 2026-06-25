<?php declare(strict_types=1);

namespace Mautic\AssetBundle\Helper;

class PointActionHelper
{
    public static function validateAssetDownload($eventDetails, $action): bool
    {
        $assetId       = $eventDetails->getId();
        $limitToAssets = $action['properties']['assets'];

        if (!empty($limitToAssets) && !in_array($assetId, $limitToAssets, true)) {
            // no points change
            return false;
        }

        return true;
    }
}
