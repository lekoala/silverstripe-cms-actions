<?php

namespace LeKoala\CmsActions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;

/**
 * When using inline editing on a ModelAdmin, there is no save button
 * This allows saving the records
 * It needs a custom endpoint because somehow, new records are not sent along
 */
class GridFieldSaveAllButton extends GridFieldTableButton
{
    protected $fontIcon = 'save';
    public bool $submitData = true;
    /**
     * @var boolean
     */
    protected $noAjax = false;
    protected ?string $completeMessage = null;

    public function __construct($targetFragment = 'buttons-before-left', $buttonLabel = null)
    {
        parent::__construct($targetFragment, $buttonLabel);
        $this->buttonLabel = $buttonLabel ?? _t('GridFieldSaveAllButton.SaveAll', 'Save all');
    }

    public function handle(GridField $gridField, Controller $controller, $arguments = [], $data = [])
    {
        $fieldName = $gridField->getName();
        $list = $gridField->getList();
        $model = $gridField->getModelClass();

        // Without this, handleSave does not work
        $gridField->setSubmittedValue($data[$fieldName]);

        $updatedData = $data[$fieldName]['GridFieldEditableColumns'] ?? [];
        foreach ($updatedData as $id => $values) {
            /** @var DataObject $record */
            $record = $list->byID($id);
            if (!$record) {
                continue;
            }
            $component = $gridField->getConfig()->getComponentByType(\Symbiote\GridFieldExtensions\GridFieldEditableColumns::class);
            $component->handleSave($gridField, $record);
            // foreach ($values as $k => $v) {
            //     $record->$k = $v;
            // }
            // $record->write();
        }
        $newData = $data[$fieldName]['GridFieldAddNewInlineButton'] ?? [];
        foreach ($newData as $idx => $values) {
            $record = new $model;
            foreach ($values as $k => $v) {
                $record->$k = $v;
            }
            $record->write();
        }

        $response = $controller->getResponse();

        if (Director::is_ajax()) {
            if (!$this->completeMessage) {
                $this->completeMessage = _t('GridFieldSaveAllButton.DONE', 'ALL SAVED!');
            }
            // Reload for now since we mess up with the PJAX fragment
            $url = $controller->getReferer();
            $response->addHeader('X-ControllerURL', $url);
            $response->addHeader('X-Reload', true);
            $response->addHeader('X-Status', rawurlencode($this->completeMessage));
        } else {
            return $controller->redirectBack();
        }
    }

    /**
     * Get the value of completeMessage
     */
    public function getCompleteMessage(): string
    {
        return $this->completeMessage;
    }

    /**
     * Set the value of completeMessage
     *
     * @param string $completeMessage
     */
    public function setCompleteMessage($completeMessage): self
    {
        $this->completeMessage = $completeMessage;
        return $this;
    }
}
