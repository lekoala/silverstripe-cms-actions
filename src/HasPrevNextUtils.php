<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;

/**
 * Add prev next in utils
 */
trait HasPrevNextUtils
{
    /**
     * @param FieldList $utils
     * @return FieldList
     */
    public function addPrevNextUtils(FieldList $utils)
    {
        $controller = Controller::curr();
        $request = $controller->getRequest();
        $url = rtrim($request->getURL(), '/') . '/';

        $getPreviousRecordID = $request->param('PreviousRecordID');
        $getNextRecordID = $request->param('NextRecordID');

        $search = sprintf('/%d/', $this->ID);
        $replaceStr = '/%d/';
        if ($this->ID && $getPreviousRecordID) {
            $utils->unshift(
                $NextBtnLink = new CmsInlineFormAction(
                    'NextBtnLink',
                    _t('HasPrevNextUtils.Next', 'Next') . ' >',
                    'btn-secondary')
            );
            $NextBtnLink->setLink(str_replace($search, sprintf($replaceStr, $getPreviousRecordID), $url));
        }
        if ($this->ID && $getNextRecordID) {
            $utils->unshift(
                $PrevBtnLink = new CmsInlineFormAction(
                    'PrevBtnLink',
                    '< ' . _t('HasPrevNextUtils.Previous', 'Previous'),
                    'btn-secondary')
            );
            $PrevBtnLink->setLink(str_replace($search, sprintf($replaceStr, $getNextRecordID), $url));
        }

        return $utils;
    }
}
