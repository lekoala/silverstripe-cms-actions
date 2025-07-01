<?php

namespace LeKoala\CmsActions\Test;

use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use LeKoala\CmsActions\CustomLink;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

/**
 * Tests for Cms Actions module
 */
class CmsActionsTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'CmsActionsTest.yml';
    protected static $fixture_file_simple = 'CmsActionsSimpleTest.yml';

    protected static $extra_dataobjects = [
        Test_CmsActionsModel::class,
    ];

    public static function get_fixture_file()
    {
        if (class_exists(SiteTree::class)) {
            return self::$fixture_file;
        }
        return self::$fixture_file_simple;
    }

    public static function getExtraDataObjects()
    {
        $arr = parent::getExtraDataObjects();
        if (class_exists(SiteTree::class)) {
            $arr[] = Test_ActionsPage::class;
        }
        return $arr;
    }


    public function setUp(): void
    {
        parent::setUp();
        $controller = Controller::curr();
        $controller->config()->set('url_segment', 'test_controller');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return Test_ActionsPage
     */
    public function getTestPage()
    {
        return $this->objFromFixture(Test_ActionsPage::class, 'demo');
    }

    /**
     * @return Test_CmsActionsModel
     */
    public function getTestModel()
    {
        return $this->objFromFixture(Test_CmsActionsModel::class, 'demo');
    }

    /**
     * @return Member
     */
    public function getAdminMember()
    {
        return $this->objFromFixture(Member::class, 'admin');
    }

    /**
     * @return Form
     */
    public function getMemberForm()
    {
        $controller = Controller::curr();
        $form = new Form($controller);

        $record = $this->getAdminMember();

        $list = Member::get();
        $gridField = new GridField('testGridfield', null, $list);
        $detailForm = new GridFieldDetailForm('testDetailForm');
        $GridFieldDetailForm = new GridFieldDetailForm_ItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        $form = $GridFieldDetailForm->ItemEditForm();
        $form->loadDataFrom($record);

        return $form;
    }

    /**
     * @param Controller $controller
     * @param DataObject $record
     * @return Form
     */
    public function getTestForm($controller = null, $record = null)
    {
        if (!$controller) {
            $controller = Controller::curr();
        }
        if (!$record) {
            $record = $this->getTestModel();
        }
        $list = Test_CmsActionsModel::get();
        $gridField = new GridField('testGridfield', null, $list);
        $detailForm = new GridFieldDetailForm('testDetailForm');
        if ($record->hasExtension(Versioned::class)) {
            $GridFieldDetailForm = new VersionedGridFieldItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        } else {
            $GridFieldDetailForm = new GridFieldDetailForm_ItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        }
        $form = $GridFieldDetailForm->ItemEditForm();
        $form->loadDataFrom($record);

        return $form;
    }

    /**
     * @param Controller $controller
     * @param DataObject $record
     * @return Form
     */
    public function getViewableForm($controller = null, $record = null)
    {
        $r1 = ArrayData::create([
            'ID' => 1,
            'FieldName' => 'This is an item',
        ]);
        $r2 = ArrayData::create([
            'ID' => 2,
            'FieldName' => 'This is a different item',
        ]);

        if (!$controller) {
            $controller = Controller::curr();
        }
        if (!$record) {
            $record = $r1;
        }

        $list = ArrayList::create([
            $r1,
            $r2,
        ]);

        $gridField = GridField::create('MyData', 'My data', $list);
        $gridField->setForm(new Form($controller, "TestForm"));
        $gridField->getConfig()->removeComponentsByType(GridFieldFilterHeader::class);
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields([
            'FieldName' => 'Column Header Label',
        ]);
        $detailForm = GridFieldDetailForm::create();
        $detailForm->setFields(FieldList::create([
            HiddenField::create('ID'),
            TextField::create('FieldName', 'View Field Label'),
        ]));
        $gridField->getConfig()->addComponents([
            GridFieldViewButton::create(),
            $detailForm,
        ]);
        $GridFieldDetailForm = new GridFieldDetailForm_ItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        $form = $GridFieldDetailForm->ItemEditForm();
        $form->loadDataFrom($record);

        return $form;
    }

    public function testCustomDeleteTitle()
    {
        $form = $this->getTestForm();

        /** @var Test_CmsActionsModel $record */
        $record = $form->getRecord();

        $delete = $form->Actions()->fieldByName("action_doDelete");
        $this->assertEquals($delete->Title(), $record->getDeleteButtonTitle());
    }

    public function testHasSaveAndClose()
    {
        $form = $this->getTestForm();

        $doSaveAndClose = $form->Actions()->fieldByName("action_doSaveAndClose");
        // It can be nested in MajorActions, then we need to use dot notation
        if (!$doSaveAndClose) {
            $doSaveAndClose = $form->Actions()->fieldByName("MajorActions.action_doSaveAndClose");
        }
        $this->assertNotEmpty($doSaveAndClose);
    }

    public function testHasDefaultTitle()
    {
        $customLink = new CustomLink('doTest');
        $this->assertEquals('Do test', $customLink->getTitle());
    }

    public function testConfirmationMessage()
    {
        $customLink = new CustomLink('doTest');
        $customLink->setConfirmation(true);
        $this->assertStringContainsString('sure', $customLink->getConfirmation());
    }

    public function testGridFieldAction()
    {
        $form = $this->getTestForm();
        $action = new Test_GridFieldAction;

        $record = $this->getTestModel();
        $list = Test_CmsActionsModel::get();
        $gridField = new GridField('testGridfield', null, $list);
        $actionName = 'test';
        $arguments = ['ID' => $record->ID];
        $data = [];

        $result = $action->doHandle($gridField, $actionName, $arguments, $data);

        $this->assertEquals($actionName, $action->performedActionName);
        $this->assertEquals($arguments, $action->performedArguments);
        $this->assertEquals($data, $action->performedData);
    }

    public function testLeftAndMain()
    {
        if (!class_exists(SiteTree::class)) {
            $this->assertTrue(true); // make phpunit happy
            return;
        }
        $page = $this->getTestPage();
        $leftAndMain = LeftAndMain::create();
        $form = $this->getTestForm($leftAndMain, $page);

        // otherwise getRecord complains
        $leftAndMain->record = $page;
        $result = $leftAndMain->doCustomAction(
            [
                'action_doCustomAction' => [
                    'testAction' => 1
                ],
                'ID' => $page->ID,
                'ClassName' => $page->ClassName
            ],
            $form
        );

        $this->assertEquals($page->testAction(), $form->getMessage());

        $list = [];
        $simpleList = [];
        foreach ($form->Actions() as $action) {
            if ($action instanceof CompositeField) {
                $arr = [];
                foreach ($action->getChildren() as $subAction) {
                    $arr[] = $subAction->getName() . ' (' . get_class($subAction) . ')';
                    $simpleList[] = $subAction->getName();
                }
                $list[] = $arr;
            } else {
                $list[] = $action->getName() . ' (' . get_class($action) . ')';
                $simpleList[] = $action->getName();
            }
        }
        $filteredSimpleList = array_unique($simpleList);
        // We should not have duplicated actions
        $this->assertEquals($filteredSimpleList, $simpleList);
    }

    public function testGetModelLink()
    {
        $action = new CustomLink("testAction", "test");

        $controller = Controller::curr();

        // SS5 trailing slashes
        // @link https://docs.silverstripe.org/en/5/changelogs/5.0.0/#trailing-slash
        $add_trailing_slash = $controller::config()->add_trailing_slash;

        // Without an url, we link on the current controller
        $link = $action->getModelLink("testAction");
        if ($add_trailing_slash === null) {
            $this->assertEquals('test_controller/testAction/?CustomLink=testAction', $link);
        } elseif ($add_trailing_slash === false) {
            $this->assertEquals('test_controller/testAction?CustomLink=testAction', $link);
        } elseif ($add_trailing_slash === true) {
            $this->assertEquals('test_controller/testAction/?CustomLink=testAction', $link);
        }


        // in settings
        $controller->getRequest()->setUrl('admin/settings/EditForm/field/MyModel/item/1/edit');
        $link = $action->getModelLink("testAction");
        $this->assertEquals('admin/settings/EditForm/field/MyModel/item/1/doCustomLink?CustomLink=testAction', $link);

        // in model admin
        $controller->getRequest()->setUrl('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/edit');
        $link = $action->getModelLink("testAction");
        $this->assertEquals('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/doCustomLink?CustomLink=testAction', $link);

        // in model admin with just an id
        $controller->getRequest()->setUrl('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/');
        $link = $action->getModelLink("testAction");
        $this->assertEquals('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/doCustomLink?CustomLink=testAction', $link);

        // in nested grid
        $controller->getRequest()->setUrl('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/ItemEditForm/field/OtherModel/item/0/edit');
        $link = $action->getModelLink("testAction");
        $this->assertEquals('admin/model_admin/MyModel/EditForm/field/MyModel/item/0/ItemEditForm/field/OtherModel/item/0/doCustomLink?CustomLink=testAction', $link);

        $controller->getRequest()->setUrl('');
    }

    public function testViewable()
    {
        $version = LeftAndMain::create()->CMSVersionNumber();
        $form = $this->getViewableForm();

        $doSaveAndClose = $form->Actions()->fieldByName("action_doSaveAndClose");
        $this->assertNull($doSaveAndClose); // not available for ViewableData
    }
}
