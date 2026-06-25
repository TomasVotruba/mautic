<?php

namespace Page\Acceptance;

class CampaignPage
{
    public static $URL                  = 's/campaigns/view/1';
    public static $contactsTab          = '//ul[contains(@class, "nav-tabs")]/li/a[@href="#leads-container"]';
    public static $contactsTabContainer = '#leads-container';

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public static function route($param)
    {
        return static::$URL.$param;
    }
}
