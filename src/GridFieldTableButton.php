<?php

namespace LeKoala\CmsActions;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;

/**
 * Provide a simple way to declare buttons that affects a whole GridField
 *
 * This implements a URL Handler that can be called by the button
 */
abstract class GridFieldTableButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
    use ProgressiveAction;

    /**
     * Fragment to write the button to
     * @var string
     */
    protected $targetFragment;

    /**
     * @var boolean
     */
    protected $noAjax = true;

    /**
     * @var boolean
     */
    protected $allowEmptyResponse = false;

    /**
     * @var string
     */
    protected $buttonLabel;

    /**
     * @var string
     */
    protected $fontIcon;

    /**
     * @var string
     */
    protected $parentID;

    /**
     * @var string
     */
    protected $confirm;

    /**
     * @var string
     */
    protected $prompt;

    /**
     * @var string
     */
    protected $promptDefault;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param string $buttonLabel
     */
    public function __construct($targetFragment = "buttons-before-right", $buttonLabel = null)
    {
        $this->targetFragment = $targetFragment;
        if ($buttonLabel) {
            $this->buttonLabel = $buttonLabel;
        }
    }

    public function getActionName()
    {
        $class = (new ReflectionClass(get_called_class()))->getShortName();

        // ! without lowercase, in does not work
        return strtolower(str_replace('Button', '', $class));
    }

    public function getButtonLabel()
    {
        return $this->buttonLabel;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $action = $this->getActionName();

        $button = new CustomGridField_FormAction(
            $gridField,
            $action,
            $this->getButtonLabel(),
            $action,
            null
        );
        $button->addExtraClass('btn btn-secondary action_' . $action);
        if ($this->noAjax) {
            $button->addExtraClass('no-ajax');
        }
        if ($this->fontIcon) {
            $button->addExtraClass('font-icon-' . $this->fontIcon);
        }
        //TODO: replace prompt and confirm with inline js
        if ($this->prompt) {
            $button->setAttribute('data-prompt', $this->prompt);
            $promptDefault = $this->getPromptDefault();
            if ($promptDefault) {
                $button->setAttribute('data-prompt-default', $promptDefault);
            }
        }
        if ($this->progressive) {
            $button->setProgressive(true);
        }
        if ($this->confirm) {
            $button->setAttribute('data-confirm', $this->confirm);
        }
        foreach ($this->attributes as $attributeName => $attributeValue) {
            $button->setAttribute($attributeName, $attributeValue);
        }
        $button->setForm($gridField->getForm());

        return [$this->targetFragment => $button->Field()];
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @param $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return [$this->getActionName()];
    }

    /**
     * @param GridField $gridField
     * @param $actionName
     * @param $arguments
     * @param $data
     * @return array|\SilverStripe\Control\HTTPResponse|void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (in_array($actionName, $this->getActions($gridField))) {
            $controller = Controller::curr();

            if ($this->progressive) {
                // Otherwise we would need some kind of UI
                if (!Director::is_ajax()) {
                    return $controller->redirectBack();
                }
            }

            $result = $this->handle($gridField, $controller);
            if ((!$result || is_string($result)) && $this->progressive) {
                // simply increment counter and let's hope last action will return something
                $step = (int)$controller->getRequest()->postVar("progress_step");
                $total = (int)$controller->getRequest()->postVar("progress_total");
                $result = [
                    'progress_step'  => $step + 1,
                    'progress_total' => $total,
                    'message'        => $result,
                ];
            }
            if ($result) {
                // Send a json response this will be handled by cms-actions.js
                if ($this->progressive) {
                    $response = $controller->getResponse();
                    $response->addHeader('Content-Type', 'application/json');
                    $response->setBody(json_encode($result));

                    return $response;
                }

                return $result;
            }

            if ($this->allowEmptyResponse) {
                return;
            }

            // Do something!
            if ($this->noAjax || !Director::is_ajax()) {
                return $controller->redirectBack();
            } else {
                $response = $controller->getResponse();
                $response->setBody($gridField->forTemplate());
                $response
                    ->addHeader('X-Status', 'Action completed');

                return $response;
            }
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return [$this->getActionName() => 'handle'];
    }

    /**
     * @param GridField $gridField
     * @param Controller $controller
     * @return mixed
     */
    abstract public function handle(GridField $gridField, Controller $controller);

    /**
     * Get the value of fontIcon
     *
     * @return string
     */
    public function getFontIcon()
    {
        return $this->fontIcon;
    }

    /**
     * Set the value of fontIcon
     *
     * @param string $fontIcon
     *
     * @return $this
     */
    public function setFontIcon($fontIcon)
    {
        $this->fontIcon = $fontIcon;

        return $this;
    }


    /**
     * Get the parent record id
     *
     * @return int
     */
    public function getParentID()
    {
        return $this->parentID;
    }

    /**
     * Set the parent record id
     *
     * @param int $id
     * @return $this
     */
    public function setParentID($id)
    {
        $this->parentID = $id;

        return $this;
    }

    /**
     * Get the value of confirm
     *
     * @return string
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Set the value of confirm
     *
     * @param string $confirm
     * @return $this
     */
    public function setConfirm($confirm)
    {
        $this->confirm = $confirm;

        return $this;
    }

    /**
     * Get the value of prompt
     *
     * @return string
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Set the value of prompt
     *
     * @param string $prompt
     * @return $this
     */
    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * Get the value of promptDefault
     *
     * @return string
     */
    public function getPromptDefault()
    {
        return $this->promptDefault;
    }

    /**
     * Set the value of promptDefault
     *
     * @param string $promptDefault
     * @return $this
     */
    public function setPromptDefault($promptDefault)
    {
        $this->promptDefault = $promptDefault;

        return $this;
    }
}
