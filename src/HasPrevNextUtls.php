<?php

namespace LeKoala\CmsActions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Controller;

/**
 * Add prev next in utils
 */
trait HasPrevNextUtils
{
    /**
     * @param FieldList $actions
     * @return FieldList
     */
    public function addPrevNextUtils(FieldList $utils)
    {
        $controller = Controller::curr();
        $request = $controller->getRequest();
        $url = rtrim($request->getURL(), '/') . '/';

        $getPreviousRecordID = $request->param('PreviousRecordID');
        $getNextRecordID = $request->param('NextRecordID');

        if ($this->ID && $getPreviousRecordID) {
            $utils->unshift($NextBtnLink = new CmsInlineFormAction('NextBtnLink', _t('HasPrevNextUtils.Next', 'Next') . ' >', 'btn-secondary'));
            $NextBtnLink->setLink(str_replace('/' . $this->ID . '/', '/' . $getPreviousRecordID . '/', $url));
        }
        if ($this->ID &&  $getNextRecordID) {
            $utils->unshift($PrevBtnLink = new CmsInlineFormAction('PrevBtnLink', '< ' . _t('HasPrevNextUtils.Previous', 'Previous'), 'btn-secondary'));
            $PrevBtnLink->setLink(str_replace('/' . $this->ID . '/', '/' .  $getNextRecordID . '/', $url));
        }
        return $utils;
    }
}
