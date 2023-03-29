<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;

/**
 * Add prev next in utils
 * Simply call this method in your getCMSUtils
 * This is not so useful since silverstripe provides this by default now
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

        $routeParams = $request->routeParams();
        $getPreviousRecordID = $routeParams['PreviousRecordID'] ?? $request->param('PreviousRecordID');
        $getNextRecordID = $routeParams['NextRecordID'] ?? $request->param('NextRecordID');

        $search = sprintf('/%d/', $this->ID);
        $replaceStr = '/%d/';
        if ($this->ID && $getNextRecordID) {
            $utils->unshift(
                $NextBtnLink = new CmsInlineFormAction(
                    'NextBtnLink',
                    _t('HasPrevNextUtils.Next', 'Next') . ' >',
                    'btn-secondary'
                )
            );
            $NextBtnLink->setLink(str_replace($search, sprintf($replaceStr, $getNextRecordID), $url));
        }
        if ($this->ID && $getPreviousRecordID) {
            $utils->unshift(
                $PrevBtnLink = new CmsInlineFormAction(
                    'PrevBtnLink',
                    '< ' . _t('HasPrevNextUtils.Previous', 'Previous'),
                    'btn-secondary'
                )
            );
            $PrevBtnLink->setLink(str_replace($search, sprintf($replaceStr, $getPreviousRecordID), $url));
        }

        return $utils;
    }
}
