<?php

namespace LeKoala\CmsActions;

use Exception;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

/**
 * Decorates GridDetailForm_ItemRequest to use new form actions and buttons.
 * This is also applied to LeftAndMain to allow actions on pages
 *
 * This is a lightweight version of BetterButtons that use default getCMSActions functionnality
 * on DataObjects
 *
 * @link https://github.com/unclecheese/silverstripe-gridfield-betterbuttons
 * @link https://github.com/unclecheese/silverstripe-gridfield-betterbuttons/blob/master/src/Extensions/GridFieldBetterButtonsItemRequest.php
 * @property \SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest|\SilverStripe\Admin\LeftAndMain $owner
 */
class ActionsGridFieldItemRequest extends DataExtension
{
    use Configurable;

    /**
     * @config
     * @var boolean
     */
    private static $enable_save_prev_next = true;

    /**
     * @config
     * @var boolean
     */
    private static $enable_save_close = true;

    /**
     * @config
     * @var boolean
     */
    private static $enable_delete_right = true;

    /**
     * @config
     * @var boolean
     */
    private static $enable_utils_prev_next = false;

    /**
     * @var array Allowed controller actions
     */
    private static $allowed_actions = array(
        'doSaveAndClose',
        'doSaveAndNext',
        'doSaveAndPrev',
        'doCustomAction', // For CustomAction
        'doCustomLink', // For CustomLink
    );

    /**
     * @return array
     */
    protected function getAvailableActions($actions)
    {
        $list = [];
        foreach ($actions as $action) {
            $list[] = $action->getName();
        }
        return $list;
    }

    /**
     * Updates the detail form to include new form actions and buttons
     *
     * Reorganize things a bit
     *
     * @param Form The ItemEditForm object
     */
    public function updateItemEditForm($form)
    {
        $itemRequest = $this->owner;
        $record = $itemRequest->record;
        if (!$record) {
            $record = $form->getRecord();
        }
        if (!$record) {
            return;
        }

        // We get the actions as defined on our record
        $CMSActions = $record->getCMSActions();

        // We can the actions from the GridFieldDetailForm_ItemRequest
        // It sets the Save and Delete buttons + Right Group
        $actions = $form->Actions();

        // The default button group that contains the Save or Create action
        // @link https://docs.silverstripe.org/en/4/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface/#extending-the-cms-actions
        $MajorActions = $actions->fieldByName('MajorActions');

        // If it doesn't exist, push to default group
        if (!$MajorActions) {
            $MajorActions = $actions;
        }

        // Push our actions that are otherwise ignored by SilverStripe
        foreach ($CMSActions as $action) {
            $actions->push($action);
        }

        // Add extension hook
        $record->extend('onBeforeUpdateCMSActions', $actions);

        // We have a 4.4 setup, before that there was no RightGroup
        $RightGroup = $actions->fieldByName('RightGroup');

        // Insert again to make sure our actions are properly placed after apply changes
        if ($RightGroup) {
            $actions->remove($RightGroup);
            $actions->push($RightGroup);
        }

        if (self::config()->enable_save_close) {
            $this->addSaveAndClose($actions, $record);
        }

        if (self::config()->enable_save_prev_next) {
            $this->addSaveNextAndPrevious($actions, $record);
        }

        if (self::config()->enable_delete_right) {
            $this->moveCancelAndDelete($actions, $record);
        }

        // Add extension hook
        $record->extend('onAfterUpdateCMSActions', $actions);
    }

    /**
     * @param FieldList $actions
     * @param DataObject $record
     * @return void
     */
    public function moveCancelAndDelete(FieldList $actions, DataObject $record)
    {
        // We have a 4.4 setup, before that there was no RightGroup
        $RightGroup = $actions->fieldByName('RightGroup');

        // Move delete at the end
        $deleteAction = $actions->fieldByName('action_doDelete');
        if ($deleteAction) {
            // Move at the end of the stack
            $actions->remove($deleteAction);
            $actions->push($deleteAction);

            if ($RightGroup) {
                // Stack position is enough to have it on the left
            } else {
                // Only necessary pre 4.4
                $deleteAction->addExtraClass('align-right');
            }
            // Set custom titlte
            if ($record->hasMethod('getDeleteButtonTitle')) {
                $deleteAction->setTitle($record->getDeleteButtonTitle());
            }
        }
        // Move cancel at the end
        $cancelButton = $actions->fieldByName('cancelbutton');
        if ($cancelButton) {
            // Move at the end of the stack
            $actions->remove($cancelButton);
            $actions->push($cancelButton);
            if ($RightGroup) {
                // Stack position is enough to have it on the left
            } else {
                // Only necessary pre 4.4
                $cancelButton->addExtraClass('align-right');
            }
            // Set custom titlte
            if ($record->hasMethod('getCancelButtonTitle')) {
                $cancelButton->setTitle($record->getCancelButtonTitle());
            }
        }
    }

    /**
     * @param DataObject $record
     * @return int
     */
    public function getCustomPreviousRecordID(DataObject $record)
    {
        if ($record->hasMethod('PrevRecord')) {
            return $record->PrevRecord()->ID ?? 0;
        }
        return $this->owner->getPreviousRecordID();
    }

    /**
     * @param DataObject $record
     * @return int
     */
    public function getCustomNextRecordID(DataObject $record)
    {
        if ($record->hasMethod('NextRecord')) {
            return $record->NextRecord()->ID ?? 0;
        }
        return $this->owner->getNextRecordID();
    }

    /**
     * @param FieldList $actions
     * @param DataObject $record
     * @return void
     */
    public function addSaveNextAndPrevious(FieldList $actions, DataObject $record)
    {
        if (!$record->canEdit()) {
            return;
        }
        if (!$record->ID) {
            return;
        }

        $MajorActions = $actions->fieldByName('MajorActions');

        // If it doesn't exist, push to default group
        if (!$MajorActions) {
            $MajorActions = $actions;
        }

        // TODO: check why with paginator, after the first page, getPreviousRecordID/getNextRecordID tend to not work properly
        $getPreviousRecordID = $this->getCustomPreviousRecordID($record);
        $getNextRecordID = $this->getCustomNextRecordID($record);

        // Coupling for HasPrevNextUtils
        if (Controller::has_curr()) {
            $request =  Controller::curr()->getRequest();
            $routeParams = $request->routeParams();
            $routeParams['PreviousRecordID'] = $getPreviousRecordID;
            $routeParams['NextRecordID'] = $getNextRecordID;
            $request->setRouteParams($routeParams);
        }

        if ($getPreviousRecordID) {
            $doSaveAndPrev = new FormAction('doSaveAndPrev', _t('ActionsGridFieldItemRequest.SAVEANDPREVIOUS', 'Save and Previous'));
            $doSaveAndPrev->addExtraClass($this->getBtnClassForRecord($record));
            $doSaveAndPrev->addExtraClass('font-icon-angle-double-left');
            $doSaveAndPrev->setUseButtonTag(true);
            $MajorActions->push($doSaveAndPrev);
        }
        if ($getNextRecordID) {
            $doSaveAndNext = new FormAction('doSaveAndNext', _t('ActionsGridFieldItemRequest.SAVEANDNEXT', 'Save and Next'));
            $doSaveAndNext->addExtraClass($this->getBtnClassForRecord($record));
            $doSaveAndNext->addExtraClass('font-icon-angle-double-right');
            $doSaveAndNext->setUseButtonTag(true);
            $MajorActions->push($doSaveAndNext);
        }
    }

    /**
     * @param FieldList $actions
     * @param DataObject $record
     * @return void
     */
    public function addSaveAndClose(FieldList $actions, DataObject $record)
    {
        if (!$record->canEdit()) {
            return;
        }
        if (!$record->ID && !$record->canCreate()) {
            return;
        }

        $MajorActions = $actions->fieldByName('MajorActions');

        // If it doesn't exist, push to default group
        if (!$MajorActions) {
            $MajorActions = $actions;
        }

        if ($record->ID) {
            $label = _t('ActionsGridFieldItemRequest.SAVEANDCLOSE', 'Save and Close');
        } else {
            $label = _t('ActionsGridFieldItemRequest.CREATEANDCLOSE', 'Create and Close');
        }
        $saveAndClose = new FormAction('doSaveAndClose', $label);
        $saveAndClose->addExtraClass($this->getBtnClassForRecord($record));
        $saveAndClose->setAttribute('data-text-alternate', $label);
        if ($record->ID) {
            $saveAndClose->setAttribute('data-btn-alternate-add', 'btn-primary');
            $saveAndClose->setAttribute('data-btn-alternate-remove', 'btn-outline-primary');
        }
        $saveAndClose->addExtraClass('font-icon-level-up');
        $saveAndClose->setUseButtonTag(true);
        $MajorActions->push($saveAndClose);
    }

    /**
     * New and existing records have different classes
     *
     * @param DataObject $record
     * @return string
     */
    protected function getBtnClassForRecord(DataObject $record)
    {
        if ($record->ID) {
            return 'btn-outline-primary';
        }
        return 'btn-primary';
    }

    /**
     * Forward a given action to a DataObject
     *
     * Action must be declared in getCMSActions to be called
     *
     * @param string $action
     * @param array $data
     * @param Form $form
     * @return HTTPResponse|DBHTMLText|string
     */
    protected function forwardActionToRecord($action, $data = [], $form = null)
    {
        $controller = $this->getToplevelController();

        // We have an item request
        $record = null;
        if ($this->owner instanceof GridFieldDetailForm_ItemRequest) {
            $record = $this->owner->record;
        } elseif ($controller instanceof LeftAndMain) {
            if (empty($data['ClassName']) || empty($data['ID'])) {
                throw new Exception("Submitted data does not contain and ID and a ClassName");
            }
            $record = DataObject::get_by_id($data['ClassName'], $data['ID']);
        } elseif ($controller->hasMethod("getRecord")) {
            $record = $controller->getRecord();
        }

        if (!$record) {
            throw new Exception("No record to handle the action $action on " . get_class($controller));
        }
        $definedActions = $record->getCMSActions();
        // Check if the action is indeed available
        $clickedAction = null;
        if (!empty($definedActions)) {
            foreach ($definedActions as $definedAction) {
                $definedActionName = $definedAction->getName();
                if ($definedAction->hasMethod('actionName')) {
                    $definedActionName = $definedAction->actionName();
                }
                if ($definedActionName == $action) {
                    $clickedAction = $definedAction;
                }
            }
        }
        if (!$clickedAction) {
            $class = get_class($record);
            $availableActions = implode(',', $this->getAvailableActions($definedActions));
            if (!$availableActions) {
                $availableActions = "(no available actions, please check getCMSActions)";
            }
            return $this->owner->httpError(403, 'Action not available on ' . $class . '. It must be one of : ' . $availableActions);
        }
        $message = null;
        $error = false;

        // Check record BEFORE the action
        // It can be deleted by the action and it will return to the list
        $isNewRecord = $record->ID == 0;

        try {
            $result = $record->$action($data, $form, $controller);

            // We have a response
            if ($result && $result instanceof HTTPResponse) {
                return $result;
            }

            if ($result === false) {
                // Result returned an error (false)
                $error = true;
                $message = _t(
                    'ActionsGridFieldItemRequest.FAILED',
                    'Action {action} failed on {name}',
                    array(
                        'action' => $clickedAction->getTitle(),
                        'name' => $record->i18n_singular_name(),
                    )
                );
            } elseif (is_string($result)) {
                // Result is a message
                $message = $result;
            }
        } catch (Exception $ex) {
            $error = true;
            $message = $ex->getMessage();
        }

        // Build default message
        if (!$message) {
            $message = _t(
                'ActionsGridFieldItemRequest.DONE',
                'Action {action} was done on {name}',
                array(
                    'action' => $clickedAction->getTitle(),
                    'name' => $record->i18n_singular_name(),
                )
            );
        }
        $status = 'good';
        if ($error) {
            $status = 'bad';
        }

        // Progressive actions return array with json data
        if (method_exists($clickedAction, 'getProgressive') && $clickedAction->getProgressive()) {
            $response = $controller->getResponse();
            $response->addHeader('Content-Type', 'application/json');
            if ($result) {
                $response->setBody(json_encode($result));
            }
            return $response;
        }

        // We don't have a form, simply return the result
        if (!$form) {
            if ($error) {
                return $this->owner->httpError(403, $message);
            }
            return $message;
        }
        if (Director::is_ajax()) {
            $controller = $this->getToplevelController();
            $controller->getResponse()->addHeader('X-Status', rawurlencode($message));
            if (method_exists($clickedAction, 'getShouldRefresh') && $clickedAction->getShouldRefresh()) {
                $controller->getResponse()->addHeader('X-Reload', "true");
            }
            // 4xx status makes a red box
            if ($error) {
                $controller->getResponse()->setStatusCode(400);
            }
        } else {
            $form->sessionMessage($message, $status, ValidationResult::CAST_HTML);
        }
        // Redirect after action
        return $this->redirectAfterAction($isNewRecord, $record);
    }

    /**
     * Handles custom links
     *
     * Use CustomLink with default behaviour to trigger this
     *
     * See:
     * DefaultLink::getModelLink
     * GridFieldCustomLink::getLink
     *
     * @param HTTPRequest $request
     * @return HTTPResponse|DBHTMLText|string
     */
    public function doCustomLink(HTTPRequest $request)
    {
        $action = $request->getVar('CustomLink');
        return $this->forwardActionToRecord($action);
    }

    /**
     * Handles custom actions
     *
     * Use CustomAction class to trigger this
     *
     * Nested actions are submitted like this
     * [action_doCustomAction] => Array
     * (
     *   [doTestAction] => 1
     * )
     *
     * @param array The form data
     * @param Form The form object
     * @return HTTPResponse|DBHTMLText|string
     */
    public function doCustomAction($data, $form)
    {
        $action = key($data['action_doCustomAction']);
        return $this->forwardActionToRecord($action, $data, $form);
    }

    /**
     * Saves the form and goes back to list view
     *
     * @param array The form data
     * @param Form The form object
     */
    public function doSaveAndClose($data, $form)
    {
        $result = $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");
        return $controller->redirect($this->getBackLink());
    }

    /**
     * Saves the form and goes back to the next item
     *
     * @param array The form data
     * @param Form The form object
     */
    public function doSaveAndNext($data, $form)
    {
        $record = $this->owner->record;
        $result = $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");

        $getNextRecordID = $this->getCustomNextRecordID($record);
        $class = get_class($record);
        $next = $class::get()->byID($getNextRecordID);

        $link = $this->owner->getEditLink($getNextRecordID);

        // Link to a specific tab if set, see cms-actions.js
        if ($next && !empty($data['_activetab'])) {
            $link .= '#' . $data['_activetab'];
        }
        return $controller->redirect($link);
    }

    /**
     * Saves the form and goes to the previous item
     *
     * @param array The form data
     * @param Form The form object
     */
    public function doSaveAndPrev($data, $form)
    {
        $record = $this->owner->record;
        $result = $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");

        $getPreviousRecordID = $this->getCustomPreviousRecordID($record);
        $class = get_class($record);
        $prev = $class::get()->byID($getPreviousRecordID);

        $link = $this->owner->getEditLink($getPreviousRecordID);

        // Link to a specific tab if set, see cms-actions.js
        if ($prev && !empty($data['_activetab'])) {
            $link .= '#' . $data['_activetab'];
        }
        return $controller->redirect($link);
    }

    /**
     * Gets the top level controller.
     *
     * @return Controller
     * @todo  This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    protected function getToplevelController()
    {
        if ($this->owner instanceof LeftAndMain) {
            return $this->owner;
        }
        if (!$this->owner->hasMethod("getController")) {
            return Controller::curr();
        }
        $c = $this->owner->getController();
        while ($c && $c instanceof GridFieldDetailForm_ItemRequest) {
            $c = $c->getController();
        }
        return $c;
    }

    /**
     * Gets the back link
     *
     * @return string
     * @todo This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    public function getBackLink()
    {
        // TODO Coupling with CMS
        $backlink = '';
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            if ($toplevelController->hasMethod('Backlink')) {
                $backlink = $toplevelController->Backlink();
            } elseif ($this->owner->getController()->hasMethod('Breadcrumbs')) {
                $parents = $this->owner->getController()->Breadcrumbs(false)->items;
                $backlink = array_pop($parents)->Link;
            }
        }
        if (!$backlink) {
            $backlink = $toplevelController->Link();
        }
        return $backlink;
    }

    /**
     * Response object for this request after a successful save
     *
     * @param bool $isNewRecord True if this record was just created
     * @param DataObject $record
     * @return HTTPResponse|DBHTMLText|string
     * @todo  This had to be directly copied from {@link GridFieldDetailForm_ItemRequest}
     * because it is a protected method and not visible to a decorator!
     */
    protected function redirectAfterAction($isNewRecord, $record = null)
    {
        $controller = $this->getToplevelController();

        if ($controller instanceof LeftAndMain) {
            // CMSMain => redirect to show
            if ($this->owner->hasMethod("LinkPageEdit")) {
                return $controller->redirect($this->owner->LinkPageEdit($record->ID));
            }
            // Fallback
            return $controller->redirect($this->owner->Link());
        }

        if ($isNewRecord) {
            return $controller->redirect($this->owner->Link());
        } elseif ($this->owner->gridField && $this->owner->gridField->getList()->byID($this->owner->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->owner->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $url = $controller->getRequest()->getURL();
            $action = $controller->getAction();
            $noActionURL = $url;
            if ($action) {
                $noActionURL = $controller->removeAction($url, $action);
            }
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
}
