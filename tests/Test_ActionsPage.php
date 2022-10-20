<?php

namespace LeKoala\CmsActions\Test;

use SilverStripe\Dev\TestOnly;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;

if (!class_exists(SiteTree::class)) {
    return;
}

class Test_ActionsPage extends DataObject implements TestOnly
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
