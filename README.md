# SilverStripe Cms Actions module

[![Build Status](https://travis-ci.com/lekoala/silverstripe-cms-actions.svg?branch=master)](https://travis-ci.com/lekoala/silverstripe-cms-actions/)
[![scrutinizer](https://scrutinizer-ci.com/g/lekoala/silverstripe-cms-actions/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lekoala/silverstripe-cms-actions/)
[![Code coverage](https://codecov.io/gh/lekoala/silverstripe-cms-actions/branch/master/graph/badge.svg)](https://codecov.io/gh/lekoala/silverstripe-cms-actions)

## Intro

For those of you missing betterbuttons :-) Because let's face it, adding custom actions in SilverStripe is a real pain.

## How does it work ?

Well it's actually quite simple. First of all, we improve the `GridFieldItemRequest` with our `ActionsGridFieldItemRequest`.
This is heavily inspired by betterbuttons module. Thanks to this extension, our actions defined in `getCMSActions` will appear properly.

Then, we forward our requests to the model (a button declared on Member call the ItemRequest handler which forwards the action to the Member model).

We can declare things in two functions:
- As actions in getCMSActions : these are displayed next to the regular "save" button

![custom action](docs/custom-action.png "custom action")

- As utilities in getCMSUtils : these are displayed on the top right corner, next to the tabs

![cms utils](docs/cms-utils.png "cms utils")

## Add your buttons

### Actions

Simply use getCMSActions on your DataObjects and add new buttons for your DataObjects!
For this, simply push new actions. The CustomAction class is responsible of calling the
action defined on your DataObject.

In the following example, we call doCustomAction. The return string is displayed as a notification.
If not return string is specified, we display a generic message "Action {action} done on {record}".

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        $actions->push(new CustomAction("doCustomAction", "My custom action"));

        return $actions;
    }

    public function doCustomAction() {
        return 'Done!';
    }

If it throws an exception or return a false bool, it will show an error message

    public function doCustomAction() {
        throw new Exception("Show this error");
        return false;
    }

You can set icon. See SilverStripeIcons class for available icons. We use base silverstripe icons.

    $downloadExcelReport->setButtonIcon(SilverStripeIcons::ICON_EXPORT);

CustomActions are buttons and submitted through ajax. If it changes the state of your record you
may need to refresh the UI, but be careful of not losing any unsaved data.

    $myAction->setShouldRefresh(true);

Sometimes, you don't want buttons, but links. Use CustomLink instead. This is useful to, say,
download an excel report or a pdf file.

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        $actions->push($downloadExcelReport = new CustomLink('downloadExcelReport','Download report'));
        $downloadExcelReport->setButtonIcon(SilverStripeIcons::ICON_EXPORT);

        return $actions;
    }

    public function downloadExcelReport() {
        echo "This is the report";
        die();
    }

Please note that are we use a die pattern that is not very clean, but you can very well return
a HTTPResponse object instead.

CustomLink opens by default a new window. You can use `setNewWindow(false)` to prevent this.
CustomLink calls by default an action on the model matching its name. But really you can point it to anything, even an external link using `setLink('https//www.silverstripe.org')`.

#### Confirm actions

If an action is potentially dangerous or avoid misclicks, you can set a confirmation message using `setConfirmation('Are you sure')` or simply pass `true` for a generic message.

### Utils

Declare getCMSUtils or use updateCMSUtils in your extensions. These utilities will
appear next to the tabs. They are ideal to provide some extra information or navigation.
I've used these to add shortcuts, timers, dropdowns navs...

    public function getCMSUtils()
    {
        $fields = new FieldList();
        $fields->push(new LiteralField("LastLoginInfo", "Last login at " . $this->LastLogin));
        return $fields;
    }

### Save and close

Add a default "save and close" or "create and close" button to quickly add DataObjects.

This feature can be disabled with the `enable_save_close` config flag

![save and close](docs/save-and-close.png "save and close")

### Delete action is on the right

Really I don't know who thought that having delete button next to a save button was a good idea, but I'd rather have it on the right end side.

This feature can be disabled with the `enable_delete_right` config flag

![delete btn](docs/delete-btn.png "delete btn")

### Prev/next support

SilverStripe 4.4 introduced a more refined UI for prev/next records. However, it only allows
navigation and does not support "save and next" or "previous and next" which is useful
when you edit records in a row.

This feature can be disabled with the `enable_save_prev_next` config flag

![save prev next](docs/save-prev-next.png "save prev next")

You can also use the HasPrevNextUtils trait to add navigation in your utils as well.

## Adding actions to a GridField row

You can create new row actions by extending the `GridFieldRowButton`

All actions will be stored in a new "Actions" column that supports multiple actions.
This can be used, for example, to download files, like invoices, etc directly from a GridField.

For example, you can do this:

    class MyRowAction extends GridFieldRowButton
    {
        protected $fontIcon = 'torso';

        public function getActionName()
        {
            return 'my_action';
        }

        public function getButtonLabel()
        {
            return 'My Action';
        }

        public function doHandle(GridField $gridField, $actionName, $arguments, $data)
        {
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }
            // Do something with item
            // Or maybe download a file...
            return Controller::curr()->redirectBack();
        }
    }

And use it in your ModelAdmin like so:

    public function getGridFieldFrom(Form $form)
    {
        return $form->Fields()->dataFieldByName($this->getSanitisedModelClass());
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridfield = $this->getGridFieldFrom($form);

        if ($this->modelClass == MyModel::class) {
            $gridfield->getConfig()->addComponent(new MyRowAction());
        }

        return $form;
    }

## Adding links to GridField

If actions are not your cup of tea, you can also add links to your GridField.

Again, it will be added to the Actions column.

This acts like a CustomLink describe above, so if we go back to our report example, we get this:

    $gridfield->getConfig()->addComponent(new GridFieldCustomLink('downloadExcelReport', 'Download Report'));

![gridfield row actions](docs/gridfield-row-actions.png "gridfield row actions")

For security reasons, the action MUST be declared in getCMSActions. Failing to do so will return a
helpful error message. If you do not want to display the button in the detail form, simply
set a d-none on it:

    $actions->push($downloadExcelReport = new CustomLink('downloadExcelReport', 'Download report'));
    $downloadExcelReport->addExtraClass('d-none');

## Adding buttons to a whole GridField

This is done using GridFieldTableButton

    class MyGridButton extends GridFieldTableButton
    {
        protected $buttonLabel = 'Do stuff';
        protected $fontIcon = 'do_stuff';

        public function handle(GridField $gridField, Controller $controller)
        {
        }
    }

This class can then be added as a regular GridField component

## Adding actions in getCmsFields

If you have a lot of actions, sometimes it might make more sense to add it to your cms fields.
I've used this to provide template files for instance that needs to be uploaded.

This is done using the `CmsInlineFormAction` class. Please note that the `doCustomAction` function must be declared on your controller, not on the model.

This is due to the fact that we are not submitting the form, therefore we are not processing the record with our `ActionsGridFieldItemRequest`.

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Actions', new CmsInlineFormAction('doCustomAction', 'Do this'));

        return $fields;
    }

In your admin class

    // don't forget to add me to allowed_actions as well
    function doCustomAction()
    {
        // do something here
        return $this->redirectBack();
    }

## Extensions support

If your extensions depend on this module, you can play with `DataObject::onBeforeUpdateCMSActions` and `DataObject::onAfterUpdateCMSActions` extension hook to add your own buttons.
This is called after all buttons have been defined.

See for instance how it's done in my [softdelete module](https://github.com/lekoala/silverstripe-softdelete).

## Todo

- Support 4.7 properly (no-ajax buttons are still submitted through ajax)
- Explore pages or siteconfig support
- Support on cms profile for members
- Mobile ui for utils / Group many buttons into drop
- Svg icons?

## Sponsored by

This module is kindly sponsored by [RESTRUCT](restruct.nl)

## Compatibility

Tested with 4.6 but should work on any ^4.4 projects
Not working perfectly on 4.7 at the moment due to some admin js changes

## Maintainer

LeKoala - thomas@lekoala.be
