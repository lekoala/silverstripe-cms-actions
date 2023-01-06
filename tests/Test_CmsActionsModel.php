<?php

namespace LeKoala\CmsActions\Test;

use LeKoala\CmsActions\ActionButtonsGroup;
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

        $groupedButtons = [
            new CustomAction("groupedAction1", "Grouped Action 1"),
            new CustomAction("groupedAction2", "Grouped Action 2"),
        ];
        $btnGroup = new ActionButtonsGroup($groupedButtons);
        $actions->push($btnGroup);

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

    public function testAction()
    {
        return 'called testAction';
    }

    public function groupedAction1()
    {
        return 'called groupedAction1';
    }

    public function groupedAction2()
    {
        return 'called groupedAction2';
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
