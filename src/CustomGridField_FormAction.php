<?php

namespace LeKoala\CmsActions;

use SilverStripe\Forms\GridField\GridField_FormAction;

class CustomGridField_FormAction extends GridField_FormAction
{
    use ProgressiveAction;

    public function Type()
    {
        if ($this->progressive) {
            return 'progressive-action';
        }
        return 'action';
    }
}
