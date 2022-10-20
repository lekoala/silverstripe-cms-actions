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

    protected static $extra_dataobjects = array(
        Test_CmsActionsModel::class,
    );

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
}
