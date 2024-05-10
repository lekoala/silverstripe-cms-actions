<?php

namespace LeKoala\CmsActions;

use Exception;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Extensible;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\CompositeField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\GridField\GridFieldStateManagerInterface;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use ReflectionObject;

/**
 * Decorates GridDetailForm_ItemRequest to use new form actions and buttons.
 *
 * This is also applied to LeftAndMain to allow actions on pages
 * Warning: LeftAndMain doesn't call updateItemEditForm
 *
 * This is a lightweight version of BetterButtons that use default getCMSActions functionnality
 * on DataObjects
 *
 * @link https://github.com/unclecheese/silverstripe-gridfield-betterbuttons
 * @link https://github.com/unclecheese/silverstripe-gridfield-betterbuttons/blob/master/src/Extensions/GridFieldBetterButtonsItemRequest.php
 * @property LeftAndMain&GridFieldDetailForm_ItemRequest&ActionsGridFieldItemRequest $owner
 */
class ActionsGridFieldItemRequest extends DataExtension
{
    use Configurable;
    use Extensible;

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
     * @var array<string> Allowed controller actions
     */
    private static $allowed_actions = [
        'doSaveAndClose',
        'doSaveAndNext',
        'doSaveAndPrev',
        'doCustomAction', // For CustomAction
        'doCustomLink', // For CustomLink
    ];

    /**
     * @param FieldList $actions
     * @return array<string>
     */
    protected function getAvailableActions($actions)
    {
        $list = [];
        foreach ($actions as $action) {
            if (is_a($action, CompositeField::class)) {
                $list = array_merge($list, $this->getAvailableActions($action->FieldList()));
            } else {
                $list[] = $action->getName();
            }
        }

        return $list;
    }

    /**
     * This module does not interact with the /schema/SearchForm endpoint
     * and therefore all requests for these urls don't need any special treatement
     *
     * @return bool
     */
    protected function isSearchFormRequest(): bool
    {
        if (!Controller::has_curr()) {
            return false;
        }
        $curr =  Controller::curr();
        if ($curr) {
            return str_contains($curr->getRequest()->getURL(), '/schema/SearchForm');
        }
        return false;
    }

    /**
     * Called by CMSMain, typically in the CMS or in the SiteConfig admin
     * CMSMain already uses getCMSActions so we are good to go with anything defined there
     *
     * @param Form $form
     * @return void
     */
    public function updateEditForm(Form $form)
    {
        // Ignore search form requests
        if ($this->isSearchFormRequest()) {
            return;
        }

        $actions = $form->Actions();

        // We create a Drop-Up menu afterwards because it may already exist in the $CMSActions
        // and we don't want to duplicate it
        $this->processDropUpMenu($actions);
    }

    /**
     * @return FieldList|false
     */
    public function recordCmsUtils()
    {
        $record = $this->owner->getRecord();
        if ($record->hasMethod('getCMSUtils')) {
            $utils = $record->getCMSUtils();
            $this->extend('onCMSUtils', $utils, $record);
            $record->extend('onCMSUtils', $utils);
            return $utils;
        }
        return false;
    }

    /**
     * Called by GridField_ItemRequest
     * GridField_ItemRequest defines its own set of actions so we need to add ours
     * We add our custom save&close, save&next and other tweaks
     * Actions can be made readonly after this extension point
     * @param FieldList $actions
     * @return void
     */
    public function updateFormActions($actions)
    {
        // Ignore search form requests
        if ($this->isSearchFormRequest()) {
            return;
        }

        $record = $this->owner->getRecord();

        // We get the actions as defined on our record
        /** @var FieldList $CMSActions */
        $CMSActions = $record->getCMSActions();

        // The default button group that contains the Save or Create action
        // @link https://docs.silverstripe.org/en/4/developer_guides/customising_the_admin_interface/how_tos/extend_cms_interface/#extending-the-cms-actions
        $MajorActions = $actions->fieldByName('MajorActions');

        // If it doesn't exist, push to default group
        if (!$MajorActions) {
            $MajorActions = $actions;
        }

        // Push our actions that are otherwise ignored by SilverStripe
        foreach ($CMSActions as $action) {
            // Avoid duplicated actions (eg: when added by SilverStripe\Versioned\VersionedGridFieldItemRequest)
            if ($actions->fieldByName($action->getName())) {
                continue;
            }
            $actions->push($action);
        }

        // We create a Drop-Up menu afterwards because it may already exist in the $CMSActions
        // and we don't want to duplicate it
        $this->processDropUpMenu($actions);

        // Add extension hook
        $this->extend('onBeforeUpdateCMSActions', $actions, $record);
        $record->extend('onBeforeUpdateCMSActions', $actions);

        $ActionMenus = $actions->fieldByName('ActionMenus');
        // Re-insert ActionMenus to make sure they always follow the buttons
        if ($ActionMenus) {
            $actions->remove($ActionMenus);
            $actions->push($ActionMenus);
        }

        // We have a 4.4 setup, before that there was no RightGroup
        $RightGroup = $this->getRightGroupActions($actions);

        // Insert again to make sure our actions are properly placed after apply changes
        if ($RightGroup) {
            $actions->remove($RightGroup);
            $actions->push($RightGroup);
        }

        $opts = [
            'save_close'     => self::config()->enable_save_close,
            'save_prev_next' => self::config()->enable_save_prev_next,
            'delete_right'   => self::config()->enable_delete_right,
        ];
        if ($record->hasMethod('getCMSActionsOptions')) {
            $opts = array_merge($opts, $record->getCMSActionsOptions());
        }

        if ($opts['save_close']) {
            $this->addSaveAndClose($actions, $record);
        }

        if ($opts['save_prev_next']) {
            $this->addSaveNextAndPrevious($actions, $record);
        }

        if ($opts['delete_right']) {
            $this->moveCancelAndDelete($actions, $record);
        }

        // Fix gridstate being lost when running custom actions
        if (method_exists($this->owner, 'getStateManager')) {
            $request = $this->owner->getRequest();
            $stateManager = $this->owner->getStateManager();
            $gridField = $this->owner->getGridField();
            $actions->push(new HiddenField($stateManager->getStateKey($gridField), null, $stateManager->getStateFromRequest($gridField, $request)));
        }

        // Add extension hook
        $this->extend('onAfterUpdateCMSActions', $actions, $record);
        $record->extend('onAfterUpdateCMSActions', $actions);
    }

    /**
     * Collect all Drop-Up actions into a menu.
     * @param FieldList $actions
     * @return void
     */
    protected function processDropUpMenu($actions)
    {
        // The Drop-up container may already exist
        /** @var ?Tab $dropUpContainer */
        $dropUpContainer = $actions->fieldByName('ActionMenus.MoreOptions');
        foreach ($actions as $action) {
            //@phpstan-ignore-next-line
            if ($action->hasMethod('getDropUp') && $action->getDropUp()) {
                if (!$dropUpContainer) {
                    $dropUpContainer = $this->createDropUpContainer($actions);
                }
                $action->getContainerFieldList()->removeByName($action->getName());
                $dropUpContainer->push($action);
            }
        }
    }

    /**
     * Prepares a Drop-Up menu
     * @param FieldList $actions
     * @return Tab
     */
    protected function createDropUpContainer($actions)
    {
        $rootTabSet = new TabSet('ActionMenus');
        $dropUpContainer = new Tab(
            'MoreOptions',
            _t(__CLASS__ . '.MoreOptions', 'More options', 'Expands a view for more buttons')
        );
        $dropUpContainer->addExtraClass('popover-actions-simulate');
        $rootTabSet->push($dropUpContainer);
        $rootTabSet->addExtraClass('ss-ui-action-tabset action-menus noborder');

        $actions->insertBefore('RightGroup', $rootTabSet);

        return $dropUpContainer;
    }

    /**
     * Check if a record can be edited/created/exists
     * @param DataObject $record
     * @return bool
     */
    protected function checkCan($record)
    {
        if (!$record->canEdit() || (!$record->ID && !$record->canCreate())) {
            return false;
        }

        return true;
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

            if (!$RightGroup) {
                // Only necessary pre 4.4
                $deleteAction->addExtraClass('align-right');
            }
            // Set custom title
            if ($record->hasMethod('getDeleteButtonTitle')) {
                //@phpstan-ignore-next-line
                $deleteAction->setTitle($record->getDeleteButtonTitle());
            }
        }
        // Move cancel at the end
        $cancelButton = $actions->fieldByName('cancelbutton');
        if ($cancelButton) {
            // Move at the end of the stack
            $actions->remove($cancelButton);
            $actions->push($cancelButton);
            if (!$RightGroup) {
                // Only necessary pre 4.4
                $cancelButton->addExtraClass('align-right');
            }
            // Set custom titlte
            if ($record->hasMethod('getCancelButtonTitle')) {
                //@phpstan-ignore-next-line
                $cancelButton->setTitle($record->getCancelButtonTitle());
            }
        }
    }

    /**
     * @param DataObject $record
     * @return bool
     */
    public function useCustomPrevNext(DataObject $record): bool
    {
        if (self::config()->enable_custom_prevnext) {
            return $record->hasMethod('PrevRecord') && $record->hasMethod('NextRecord');
        }
        return false;
    }

    /**
     * @param DataObject $record
     * @return int
     */
    public function getCustomPreviousRecordID(DataObject $record)
    {
        // This will overwrite state provided record
        if ($this->useCustomPrevNext($record)) {
            //@phpstan-ignore-next-line
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

        // This will overwrite state provided record
        if ($this->useCustomPrevNext($record)) {
            //@phpstan-ignore-next-line
            return $record->NextRecord()->ID ?? 0;
        }
        return $this->owner->getNextRecordID();
    }

    /**
     * @param FieldList $actions
     * @return CompositeField|FieldList
     */
    protected function getMajorActions(FieldList $actions)
    {
        /** @var ?CompositeField $MajorActions */
        $MajorActions = $actions->fieldByName('MajorActions');

        // If it doesn't exist, push to default group
        if (!$MajorActions) {
            $MajorActions = $actions;
        }
        return $MajorActions;
    }

    /**
     * @param FieldList $actions
     * @return CompositeField
     */
    protected function getRightGroupActions(FieldList $actions)
    {
        /** @var ?CompositeField $RightGroup */
        $RightGroup = $actions->fieldByName('RightGroup');
        return $RightGroup;
    }

    /**
     * @param FieldList $actions
     * @param DataObject $record
     * @return void
     */
    public function addSaveNextAndPrevious(FieldList $actions, DataObject $record)
    {
        if (!$record->canEdit() || !$record->ID) {
            return;
        }

        $MajorActions = $this->getMajorActions($actions);

        // @link https://github.com/silverstripe/silverstripe-framework/issues/10742
        $getPreviousRecordID = $this->getCustomPreviousRecordID($record);
        $getNextRecordID = $this->getCustomNextRecordID($record);
        $isCustom  = $this->useCustomPrevNext($record);

        // Coupling for HasPrevNextUtils
        if (Controller::has_curr()) {
            $prevLink = $nextLink = null;
            if (!$isCustom && $this->owner instanceof GridFieldDetailForm_ItemRequest) {
                $this->owner->getStateManager();
                $reflObject = new ReflectionObject($this->owner);
                $reflMethod = $reflObject->getMethod('getEditLinkForAdjacentRecord');
                $reflMethod->setAccessible(true);

                if ($getPreviousRecordID) {
                    $prevLink = $reflMethod->invoke($this->owner, -1);
                }
                if ($getNextRecordID) {
                    $nextLink = $reflMethod->invoke($this->owner, +1);
                }
            }

            /** @var HTTPRequest $request */
            $request = Controller::curr()->getRequest();
            $routeParams = $request->routeParams();
            $recordClass = get_class($record);
            $routeParams['cmsactions'][$recordClass]['PreviousRecordID'] = $getPreviousRecordID;
            $routeParams['cmsactions'][$recordClass]['NextRecordID'] = $getNextRecordID;
            $routeParams['cmsactions'][$recordClass]['PrevRecordLink'] = $prevLink;
            $routeParams['cmsactions'][$recordClass]['NextRecordLink'] = $nextLink;
            $request->setRouteParams($routeParams);
        }

        if ($getPreviousRecordID) {
            $doSaveAndPrev = new FormAction('doSaveAndPrev', _t('ActionsGridFieldItemRequest.SAVEANDPREVIOUS', 'Save and Previous'));
            $doSaveAndPrev->addExtraClass($this->getBtnClassForRecord($record));
            $doSaveAndPrev->addExtraClass('font-icon-angle-double-left btn-mobile-collapse');
            $doSaveAndPrev->setUseButtonTag(true);
            $MajorActions->push($doSaveAndPrev);
        }
        if ($getNextRecordID) {
            $doSaveAndNext = new FormAction('doSaveAndNext', _t('ActionsGridFieldItemRequest.SAVEANDNEXT', 'Save and Next'));
            $doSaveAndNext->addExtraClass($this->getBtnClassForRecord($record));
            $doSaveAndNext->addExtraClass('font-icon-angle-double-right btn-mobile-collapse');
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
        if (!$this->checkCan($record)) {
            return;
        }

        $MajorActions = $this->getMajorActions($actions);

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
        $saveAndClose->addExtraClass('font-icon-level-up btn-mobile-collapse');
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
     * @param string $action
     * @param array<FormField>|FieldList $definedActions
     * @return mixed|CompositeField|null
     */
    protected static function findAction($action, $definedActions)
    {
        $result = null;

        foreach ($definedActions as $definedAction) {
            if (is_a($definedAction, CompositeField::class)) {
                $result = self::findAction($action, $definedAction->FieldList());
                if ($result) {
                    break;
                }
            }

            $definedActionName = $definedAction->getName();

            if ($definedAction->hasMethod('actionName')) {
                //@phpstan-ignore-next-line
                $definedActionName = $definedAction->actionName();
            }
            if ($definedActionName === $action) {
                $result = $definedAction;
                break;
            }
        }

        return $result;
    }

    /**
     * Forward a given action to a DataObject
     *
     * Action must be declared in getCMSActions to be called
     *
     * @param string $action
     * @param array<string,mixed> $data
     * @param Form $form
     * @return HTTPResponse|DBHTMLText|string
     * @throws HTTPResponse_Exception
     */
    protected function forwardActionToRecord($action, $data = [], $form = null)
    {
        $controller = $this->getToplevelController();

        // We have an item request or a controller that can provide a record
        $record = null;
        if ($this->owner->hasMethod('ItemEditForm')) {
            // It's a request handler. Don't check for a specific class as it may be subclassed
            $record = $this->owner->record;
        } elseif ($controller->hasMethod('save_siteconfig')) {
            // Check for any type of siteconfig controller
            $record = SiteConfig::current_site_config();
        } elseif (!empty($data['ClassName']) && !empty($data['ID'])) {
            $record = DataObject::get_by_id($data['ClassName'], $data['ID']);
        } elseif ($controller->hasMethod("getRecord")) {
            //@phpstan-ignore-next-line
            $record = $controller->getRecord();
        }

        if (!$record) {
            throw new Exception("No record to handle the action $action on " . get_class($controller));
        }
        $definedActions = $record->getCMSActions();
        // Check if the action is indeed available
        $clickedAction = null;
        if (!empty($definedActions)) {
            $clickedAction = self::findAction($action, $definedActions);
        }
        if (!$clickedAction) {
            $class = get_class($record);
            $availableActions = implode(',', $this->getAvailableActions($definedActions));
            if (!$availableActions) {
                $availableActions = "(no available actions, please check getCMSActions)";
            }

            return $this->owner->httpError(403, sprintf(
                'Action not available on %s. It must be one of : %s',
                $class,
                $availableActions
            ));
        }
        $message = null;
        $error = false;

        // Check record BEFORE the action
        // It can be deleted by the action, and it will return to the list
        $isNewRecord = $record->ID === 0;

        try {
            $result = $record->$action($data, $form, $controller);

            // We have a response
            if ($result instanceof HTTPResponse) {
                return $result;
            }

            if ($result === false) {
                // Result returned an error (false)
                $error = true;
                $message = _t(
                    'ActionsGridFieldItemRequest.FAILED',
                    'Action {action} failed on {name}',
                    ['action' => $clickedAction->getTitle(), 'name' => $record->i18n_singular_name()]
                );
            } elseif (is_string($result)) {
                // Result is a message
                $message = $result;
            }
        } catch (Exception $ex) {
            $result = null;
            $error = true;
            $message = $ex->getMessage();
        }

        // Build default message
        if (!$message) {
            $message = _t(
                'ActionsGridFieldItemRequest.DONE',
                'Action {action} was done on {name}',
                ['action' => $clickedAction->getTitle(), 'name' => $record->i18n_singular_name()]
            );
        }
        $status = 'good';
        if ($error) {
            $status = 'bad';
        }

        // Progressive actions return array with json data
        //@phpstan-ignore-next-line
        if (method_exists($clickedAction, 'getProgressive') && $clickedAction->getProgressive()) {
            $response = $controller->getResponse();
            $response->addHeader('Content-Type', 'application/json');
            if ($result) {
                $encodedResult = json_encode($result);
                if (!$encodedResult) {
                    $encodedResult = json_last_error_msg();
                }
                $response->setBody($encodedResult);
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
            $controller->getResponse()->addHeader('X-Status', rawurlencode($message));

            //@phpstan-ignore-next-line
            if (method_exists($clickedAction, 'getShouldRefresh') && $clickedAction->getShouldRefresh()) {
                $controller->getResponse()->addHeader('X-Reload', "true");
                // Requires a ControllerURL as well, see https://github.com/silverstripe/silverstripe-admin/blob/a3aa41cea4c4df82050eef65ad5efcfae7bfde69/client/src/legacy/LeftAndMain.js#L773-L780
                $url = $controller->getReferer();
                $controller->getResponse()->addHeader('X-ControllerURL', $url);
            }
            // 4xx status makes a red box
            if ($error) {
                $controller->getResponse()->setStatusCode(400);
            }
        } else {
            // If the controller support sessionMessage, use it instead of form
            if ($controller->hasMethod('sessionMessage')) {
                //@phpstan-ignore-next-line
                $controller->sessionMessage($message, $status, ValidationResult::CAST_HTML);
            } else {
                $form->sessionMessage($message, $status, ValidationResult::CAST_HTML);
            }
        }

        // Custom redirect
        //@phpstan-ignore-next-line
        if (method_exists($clickedAction, 'getRedirectURL') && $clickedAction->getRedirectURL()) {
            $controller->getResponse()->addHeader('X-Reload', "true"); // we probably need a full ui refresh
            //@phpstan-ignore-next-line
            return $controller->redirect($clickedAction->getRedirectURL());
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
     * @throws Exception
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
     * @param array<string,mixed> $data The form data
     * @param Form $form The form object
     * @return HTTPResponse|DBHTMLText|string
     * @throws Exception
     */
    public function doCustomAction($data, $form)
    {
        $action = key($data['action_doCustomAction']);
        return $this->forwardActionToRecord($action, $data, $form);
    }

    /**
     * Saves the form and goes back to list view
     *
     * @param array<string,mixed> $data The form data
     * @param Form $form The form object
     * @return HTTPResponse
     */
    public function doSaveAndClose($data, $form)
    {
        $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();

        $link = $this->getBackLink();
        $link = $this->addGridState($link, $data);

        $controller->getResponse()->addHeader("X-Pjax", "Content");

        // Prevent Already directed to errors
        // $controller->getResponse()->addHeader("Location", $link);

        return $controller->redirect($link);
    }

    /**
     * Saves the form and goes back to the next item
     *
     * @param array<string,mixed> $data The form data
     * @param Form $form The form object
     * @return HTTPResponse
     */
    public function doSaveAndNext($data, $form)
    {
        $record = $this->owner->record;
        $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");

        $class = get_class($record);
        if (!$class) {
            throw new Exception("Could not get class");
        }

        $getNextRecordID = $this->getCustomNextRecordID($record);

        /** @var ?DataObject $next */
        $next = $class::get()->byID($getNextRecordID);

        $link = $this->owner->getEditLink($getNextRecordID);
        $link = $this->addGridState($link, $data);

        // Link to a specific tab if set, see cms-actions.js
        if ($next && !empty($data['_activetab'])) {
            $link .= sprintf('#%s', $data['_activetab']);
        }

        // Prevent Already directed to errors
        // $controller->getResponse()->addHeader("Location", $link);

        return $controller->redirect($link);
    }

    /**
     * Saves the form and goes to the previous item
     *
     * @param array<string,mixed> $data The form data
     * @param Form $form The form object
     * @return HTTPResponse
     */
    public function doSaveAndPrev($data, $form)
    {
        $record = $this->owner->record;
        $this->owner->doSave($data, $form);
        // Redirect after save
        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader("X-Pjax", "Content");

        $class = get_class($record);
        if (!$class) {
            throw new Exception("Could not get class");
        }

        $getPreviousRecordID = $this->getCustomPreviousRecordID($record);
        if (!$class) {
            throw new Exception("Could not get class");
        }

        /** @var ?DataObject $prev */
        $prev = $class::get()->byID($getPreviousRecordID);

        $link = $this->owner->getEditLink($getPreviousRecordID);
        $link = $this->addGridState($link, $data);

        // Link to a specific tab if set, see cms-actions.js
        if ($prev && !empty($data['_activetab'])) {
            $link .= sprintf('#%s', $data['_activetab']);
        }

        // Prevent Already directed to errors
        // $controller->getResponse()->addHeader("Location", $link);

        return $controller->redirect($link);
    }

    /**
     * @param string $url
     * @param array<mixed> $data
     * @return string
     */
    protected function addGridState($url, $data)
    {
        // This should not be necessary at all if the state is correctly passed along
        $BackURL = $data['BackURL'] ?? null;
        if ($BackURL) {
            $query = parse_url($BackURL, PHP_URL_QUERY);
            if ($query) {
                $url = strtok($url, '?');
                $url .= '?' . $query;
            }
        }
        return $url;
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
        if ($this->isLeftAndMain($this->owner)) {
            return $this->owner;
        }
        if (!$this->owner->hasMethod("getController")) {
            return Controller::curr();
        }
        $controller = $this->owner->getController();
        while ($controller instanceof GridFieldDetailForm_ItemRequest) {
            $controller = $controller->getController();
        }

        return $controller;
    }

    /**
     * @param Controller $controller
     * @return boolean
     */
    protected function isLeftAndMain($controller)
    {
        return is_subclass_of($controller, LeftAndMain::class);
    }

    /**
     * Gets the back link
     *
     * @return string
     */
    public function getBackLink()
    {
        $backlink = '';
        $toplevelController = $this->getToplevelController();
        // Check for LeftAndMain and alike controllers with a Backlink or Breadcrumbs methods
        if ($toplevelController->hasMethod('Backlink')) {
            //@phpstan-ignore-next-line
            $backlink = $toplevelController->Backlink();
        } elseif ($this->owner->getController()->hasMethod('Breadcrumbs')) {
            $parents = $this->owner->getController()->Breadcrumbs(false)->items;
            $backlink = array_pop($parents)->Link;
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

        if ($this->isLeftAndMain($controller)) {
            // CMSMain => redirect to show
            if ($this->owner->hasMethod("LinkPageEdit")) {
                return $controller->redirect($this->owner->LinkPageEdit($record->ID));
            }
        }

        if ($isNewRecord) {
            return $controller->redirect($this->owner->Link());
        }
        if ($this->owner->gridField && $this->owner->gridField->getList()->byID($this->owner->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->owner->edit($controller->getRequest());
        }
        // Changes to the record properties might've excluded the record from
        // a filtered list, so return back to the main view if it can't be found
        $noActionURL = $url = $controller->getRequest()->getURL();
        if (!$url) {
            $url = '';
        }

        // The controller may not have these
        if ($controller->hasMethod('getAction')) {
            $action = $controller->getAction();
            // Handle GridField detail form editing
            if (strpos($url, 'ItemEditForm') !== false) {
                $action = 'ItemEditForm';
            }
            if ($action) {
                $noActionURL = $controller->removeAction($url, $action);
            }
        } else {
            // Simple fallback (last index of)
            $pos = strrpos($url, 'ItemEditForm');
            if (is_int($pos)) {
                $noActionURL = substr($url, 0, $pos);
            }
        }

        $controller->getRequest()->addHeader('X-Pjax', 'Content');
        return $controller->redirect($noActionURL, 302);
    }
}
