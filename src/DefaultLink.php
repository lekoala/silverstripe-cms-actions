<?php

namespace LeKoala\CmsActions;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;

/**
 * Create custom links on ModelAdmin
 */
trait DefaultLink
{
    /**
     * @var string
     */
    protected $link;

    /**
     * @var boolean
     */
    protected $newWindow = false;

    /**
     * Build a url to call an action on current model
     *
     * Takes into account ModelAdmin current model and set some defaults parameters
     * to send along
     *
     * If you want to call actions on the controller (ModelAdmin), use getControllerLink
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getModelLink($action, array $params = null)
    {
        if ($params === null) {
            $params = [];
        }
        $ctrl = Controller::curr();
        $fieldName = $ctrl->getRequest()->param('ModelClass');
        $allParams = $ctrl->getRequest()->allParams();
        $ID = $allParams['ID'] ?? 0;
        $fieldName = $fieldName ?? $allParams['OtherID'];
        $params = array_merge(['CustomLink' => $action], $params);
        $action = sprintf('/EditForm/field/%s/item/%d/doCustomLink', $fieldName, $ID);

        return $this->getControllerLink($action, $params);
    }

    /**
     * Build an url for the current controller and pass along some parameters
     *
     * If you want to call actions on a model, use getModelLink
     *
     * @param string $action
     * @param array|null $params
     * @return string
     */
    public function getControllerLink($action, array $params = null)
    {
        if ($params === null) {
            $params = [];
        }
        $ctrl = Controller::curr();
        if ($ctrl instanceof ModelAdmin) {
            $allParams = $ctrl->getRequest()->allParams();
            $modelClass = $ctrl->getRequest()->param('ModelClass');
            $action = sprintf('%s/%s', $modelClass, $action);
            $params = array_merge($allParams, $params);
        }
        if (!empty($params)) {
            $action .= '?' . http_build_query($params);
        }

        return $ctrl->Link($action);
    }

    /**
     * Get the value of link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set the value of link
     *
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get the value of newWindow
     */
    public function getNewWindow()
    {
        return $this->newWindow;
    }

    /**
     * Set the value of newWindow
     *
     * @return $this
     */
    public function setNewWindow($newWindow)
    {
        $this->newWindow = $newWindow;

        return $this;
    }
}
