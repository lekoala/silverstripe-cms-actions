<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;

/**
 * Add prev next in utils
 * Simply call `addPrevNextUtils` method in your getCMSUtils
 * This is not so useful anymore since silverstripe provides this by default now
 * but can help if you use custom prev/next logic
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

        $query = $_GET;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $routeParams = $request->routeParams();
        $recordClass = get_class($this);
        $getPreviousRecordID = $routeParams['cmsactions'][$recordClass]['PreviousRecordID'] ?? $request->param('PreviousRecordID');
        $getNextRecordID = $routeParams['cmsactions'][$recordClass]['NextRecordID'] ?? $request->param('NextRecordID');

        $search = sprintf('/%d/', $this->ID);
        $replaceStr = '/%d/';
        $PrevRecordLink = $routeParams['cmsactions'][$recordClass]['PrevRecordLink'] ?? null;
        $NextRecordLink = $routeParams['cmsactions'][$recordClass]['NextRecordLink'] ?? null;
        if (!$PrevRecordLink) {
            $PrevRecordLink = str_replace($search, sprintf($replaceStr, $getPreviousRecordID), $url);
        }
        if (!$NextRecordLink) {
            $NextRecordLink = str_replace($search, sprintf($replaceStr, $getNextRecordID), $url);
        }

        if ($this->ID && $getNextRecordID) {
            $utils->unshift(
                $NextBtnLink = CmsInlineFormAction::create('NextBtnLink', _t('HasPrevNextUtils.Next', 'Next') . ' >', 'btn-secondary')
            );
            $NextBtnLink->setLink($NextRecordLink);
        }
        if ($this->ID && $getPreviousRecordID) {
            $utils->unshift(
                $PrevBtnLink = CmsInlineFormAction::create('PrevBtnLink', '< ' . _t('HasPrevNextUtils.Previous', 'Previous'), 'btn-secondary')
            );
            $PrevBtnLink->setLink($PrevRecordLink);
        }

        return $utils;
    }
}
