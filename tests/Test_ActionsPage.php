<?php

namespace LeKoala\CmsActions\Test;

use SilverStripe\Dev\TestOnly;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\CMS\Model\SiteTree;

class Test_ActionsPage extends SiteTree implements TestOnly
{
    private static $table_name = 'ActionsPage';

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        $actions->push(new CustomAction('testAction', 'Test Action'));
        return $actions;
    }

    public function testAction()
    {
        return 'called testAction';
    }
}
