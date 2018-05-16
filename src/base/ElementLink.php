<?php
namespace kuriousagency\listingsource\base;

use kuriousagency\listingsource\Listingsource;

use Craft;
use craft\base\Element;

abstract class ElementLink extends Link
{
    // Private
    // =========================================================================

    private $_element;

    // Static
    // =========================================================================

    public static function group(): string
    {
        return Craft::t('listingsource', 'Element');
    }

    public static function elementType()
    {
        return Element::class;
    }

    public static function settingsTemplatePath(): string
    {
        return 'listingsource/types/settings/_element';
    }

    public static function inputTemplatePath(): string
    {
        return 'listingsource/types/input/_element';
    }

    // Public
    // =========================================================================

    public $sources = '*';
    public $customSelectionLabel;

    // Public Methods
    // =========================================================================

    public function defaultSelectionLabel(): string
    {
        return Craft::t('listingsource', 'Select') . ' ' . $this->defaultLabel();
    }

    public function getSelectionLabel(): string
    {
        if(!is_null($this->customSelectionLabel) && $this->customSelectionLabel != '')
        {
            return $this->customSelectionLabel;
        }
        return $this->defaultSelectionLabel();
    }

    public function getUrl(): string
    {
        if(!$this->getElement())
        {
            return '';
        }
        return $this->getElement()->getUrl() ?? '';
    }

    public function getText(): string
    {
        return $this->getCustomOrDefaultText() ?? $this->getElement()->title ?? $this->getUrl() ?? '';
    }

    public function getElement()
    {
        if(is_null($this->_element))
        {
            $this->_element = Craft::$app->getElements()->getElementById((int) $this->value);
        }
        return $this->_element;
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['customSelectionLabel', 'string'];
        return $rules;
    }

    public function getSourceOptions(): array
    {
        return Listingsource::$plugin->service->getSourceOptions($this->elementType(),$this->getTypeHandle());
    }
}
