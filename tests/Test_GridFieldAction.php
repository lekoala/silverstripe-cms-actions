<?php

namespace LeKoala\CmsActions\Test;

use SilverStripe\Dev\TestOnly;
use LeKoala\CmsActions\GridFieldRowButton;
use SilverStripe\Forms\GridField\GridField;

/**
 * A test action
 */
class Test_GridFieldAction extends GridFieldRowButton implements TestOnly
{
    protected $fontIcon = 'torso';
    public $performedActionName;
    public $performedArguments;
    public $performedData;

    public function getActionName()
    {
        return 'test';
    }

    public function getButtonLabel()
    {
        return 'Do Test';
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function doHandle(GridField $gridField, $actionName, $arguments, $data)
    {
        $this->performedActionName = $actionName;
        $this->performedArguments = $arguments;
        $this->performedData = $data;
    }
}
