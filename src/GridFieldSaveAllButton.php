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
    protected ?bool $useHandleSave = true;
    protected $allowEmptyResponse = true;
    protected bool $shouldReload = false;

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
            // You can use the grid field component or a simple loop with write
            if ($this->useHandleSave) {
                /** @var \Symbiote\GridFieldExtensions\GridFieldEditableColumns $component */
                $component = $gridField->getConfig()->getComponentByType(\Symbiote\GridFieldExtensions\GridFieldEditableColumns::class);
                $component->handleSave($gridField, $record);
            } else {
                foreach ($values as $k => $v) {
                    $record->$k = $v;
                }
                $record->write();
            }
        }
        $newData = $data[$fieldName]['GridFieldAddNewInlineButton'] ?? [];
        foreach ($newData as $idx => $values) {
            if ($this->useHandleSave) {
                /** @var \Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton $component */
                $component = $gridField->getConfig()->getComponentByType(\Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton::class);
                $component->handleSave($gridField, $record);
            } else {
                $record = new $model;
                foreach ($values as $k => $v) {
                    $record->$k = $v;
                }
                $record->write();
            }
        }

        $response = $controller->getResponse();

        if (Director::is_ajax()) {
            if (!$this->completeMessage) {
                $this->completeMessage = _t('GridFieldSaveAllButton.DONE', 'All saved');
            }
            if ($this->shouldReload) {
                ActionsGridFieldItemRequest::addXReload($controller);
            }
            $response->addHeader('X-Status', rawurlencode($this->completeMessage));
            return null;
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

    /**
     * Get the value of useHandleSave
     */
    public function getUseHandleSave(): bool
    {
        return $this->useHandleSave;
    }

    /**
     * Set the value of useHandleSave
     *
     * @param bool $useHandleSave
     */
    public function setUseHandleSave($useHandleSave): self
    {
        $this->useHandleSave = $useHandleSave;
        return $this;
    }

    /**
     * Get the value of shouldReload
     */
    public function getShouldReload(): bool
    {
        return $this->shouldReload;
    }

    /**
     * Set the value of shouldReload
     *
     * @param bool $shouldReload
     */
    public function setShouldReload($shouldReload): self
    {
        $this->shouldReload = $shouldReload;
        return $this;
    }
}
