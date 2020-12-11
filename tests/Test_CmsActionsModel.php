<?php

namespace LeKoala\CmsActions\Test;

use LeKoala\CmsActions\CustomAction;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

class Test_CmsActionsModel extends DataObject implements TestOnly
{
    private static $db = [
        "Name" => "Varchar",
    ];
    private static $has_one = [];
    private static $table_name = 'CmsActionsModel';

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        $actions->push(new CustomAction('testAction', 'Test Action'));
        return $actions;
    }

    public function getCMSUtils()
    {
        $utils = new FieldList();
        return $utils;
    }

    public function canDelete($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }

    public function getDeleteButtonTitle()
    {
        return 'Delete this!';
    }

    public function getCancelButtonTitle()
    {
        return 'Maybe not!';
    }
}
