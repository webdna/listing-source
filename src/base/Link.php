<?php
namespace kuriousagency\listingsource\base;

use kuriousagency\listingsource\helpers\ListingsourceHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\SavableComponent;
use craft\helpers\Template as TemplateHelper;

abstract class Link extends SavableComponent implements LinkInterface
{
    // Static
    // =========================================================================

    public static function group(): string
    {
        return Craft::t('listingsource', 'Basic');
    }

    public static function groupTitle(): string
    {
        return static::group().' '.Craft::t('listingsource', 'Links');
    }

    public static function defaultLabel(): string
    {
        $classNameParts = explode('\\', static::class);
        return array_pop($classNameParts);
    }

    public static function defaultPlaceholder(): string
    {
        return static::defaultLabel();
    }

    public static function settingsTemplatePath(): string
    {
        return 'listingsource/types/settings/_default';
    }

    public static function inputTemplatePath(): string
    {
        return 'listingsource/types/input/_default';
    }

    public static function hasSettings(): bool
    {
        return true;
    }

    // Public
    // =========================================================================

    public $customLabel;

    public $fieldSettings;
    public $value;
    public $customText;
    public $target;

    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return $this->getLink([], false);
    }

    public function defaultSelectionLabel(): string
    {
        return Craft::t('listingsource', 'Select') . ' ' . $this->defaultLabel();
    }

    public function getType(): string
    {
        return get_class($this);
    }

    public function getTypeHandle(): string
    {
        $typeParts = explode('\\', $this->type);
        return strtolower(array_pop($typeParts));
    }

    public function getLabel(): string
    {
        if(!is_null($this->customLabel) && $this->customLabel != '')
        {
            return $this->customLabel;
        }
        return static::defaultLabel();
    }

    public function getSelectionLabel(): string
    {
        return $this->defaultSelectionLabel();
    }

    public function getSettingsHtml(): string
    {
       return Craft::$app->getView()->renderTemplate(
            static::settingsTemplatePath(),
            [
                'type' => $this,
            ]
        );
    }

    public function getInputHtml(string $name, Link $currentLink = null, ElementInterface $element = null): string
    {
        
        // Settings
        $settings = $this->fieldSettings;       

        $elementSettings = $settings['types'][$this->getType()];
        $options = [];
        
        $elementSettings['sources'] = $elementSettings['sources'] == "" ? "*" : $elementSettings['sources'];

        // craft::dd($elementSettings);     
        if( ($this->getTypeHandle() == "channel") || ($this->getTypeHandle() == "entry") ) {

            $entries = [];
            $sections = Craft::$app->sections->getAllSections();            

            foreach($sections as $section){
                if($section->type == 'channel'){
                    if($elementSettings['sources'] == '*' || in_array($section->id, $elementSettings['sources'])){
                        $options[] = [
                            'label' => $section->name,
                            'value' => $section->id,
                        ];
                    }
                }
                if($section->type == 'structure'){
                    if($elementSettings['sources'] == '*' || in_array($section->id, $elementSettings['sources'])){
                        $entries[] = 'section:'.$section->id;
                    }
                }
            }

        } elseif($this->getTypeHandle() == "group") {

            $categories = Craft::$app->categories->getAllGroups();         

            foreach($categories as $categoryGroup) {
                if($elementSettings['sources'] == '*' || in_array($categoryGroup->id, $elementSettings['sources'])){
                    $options[] = [
                        'label' => $categoryGroup->name,
                        'value' => $categoryGroup->id,
                    ];
                }               
            }

        }
        
        //display channels
        if(($this->getTypeHandle() == "group") || ($this->getTypeHandle() == "channel")) {            

            return Craft::$app->getView()->renderTemplate(
                static::inputTemplatePath(),
                [
                    'name' => $name,
                    'link' => $this,
                    'currentLink' => $currentLink,
                    'element' => $element,
                    'options' => $options,
                ]
            );

        } else {

            $sources = null;
            
            if($this->getTypeHandle() == "entry") {
                $sources = $entries;
            }
            else {
                $sources = $this->sources;
            }

            return Craft::$app->getView()->renderTemplate(
                static::inputTemplatePath(),
                [
                    'name' => $name,
                    'link' => $this,
                    'currentLink' => $currentLink,
                    'element' => $element,
                    'sources' => $sources
                ]
            );

        }

    }

    public function getLink($customAttributes = [], $raw = true)
    {
        $html = ListingsourceHelper::getLinkHtml($this->getUrl(), $this->text, $this->prepLinkAttributes($customAttributes));
        return $raw ? TemplateHelper::raw($html) : $html;
    }

    public function getUrl(): string
    {
        return (string) $this->value;
    }

    public function getText(): string
    {
        if($this->fieldSettings['allowCustomText'] && $this->customText != '')
        {
            return $this->customText;
        }
        return $this->fieldSettings['defaultText'] != '' ? $this->fieldSettings['defaultText'] : $this->value ?? '';
    }

    public function getLinkAttributes(): array
    {
        $attributes = [];
        if($this->fieldSettings['allowTarget'] && $this->target)
        {
            // Target="_blank" - the most underestimated vulnerability ever
            // https://www.jitbit.com/alexblog/256-targetblank---the-most-underestimated-vulnerability-ever/
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
        }
        return $attributes;
    }

    public function getTargetString(): string
    {
        return $this->fieldSettings['allowTarget'] && $this->target ? '_blank' : '_self';
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['customLabel', 'string'];
        return $rules;
    }

    public function validateLinkValue(): bool
    {
        return true;
    }

    // Protected Methods
    // =========================================================================

    protected function prepLinkAttributes($customAttributes = []): array
    {
        return array_merge($this->getLinkAttributes(), $customAttributes);;
    }

    protected function getCustomOrDefaultText()
    {
        if($this->fieldSettings['allowCustomText'] && $this->customText != '')
        {
            return $this->customText;
        }

        if($this->fieldSettings['defaultText'] && $this->fieldSettings['defaultText'] != '')
        {
            return $this->fieldSettings['defaultText'];
        }

        return null;
    }
}
