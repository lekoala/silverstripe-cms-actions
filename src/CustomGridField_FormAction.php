<?php

namespace LeKoala\CmsActions;

use SilverStripe\Forms\GridField\GridField_FormAction;

class CustomGridField_FormAction extends GridField_FormAction
{
    use ProgressiveAction;

    public bool $submitData = false;

    /**
     * @return string
     */
    public function Type()
    {
        // if ($this->submitData) {
        //     return 'submit-action';
        // }
        if ($this->progressive) {
            return 'progressive-action';
        }
        return 'action';
    }

    public function getAttributes()
    {
        $attrs = parent::getAttributes();

        if ($this->submitData) {
            $attrs['type'] = 'submit';
        }

        return $attrs;
    }
}
