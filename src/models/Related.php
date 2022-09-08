<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\models;

use webdna\listingsource\ListingSource;

use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\Category;
use craft\helpers\Json;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class Related extends Model
{
    // Public Properties
    // =========================================================================

    public mixed $sources = '*';
    public mixed $value = null;
    public string $attribute = '';
    public string $order = 'asc';
    public int $total = 0;
    public bool $pagination = false;
    public bool $sticky = false;
    public bool $featured = false;

    private ?ElementInterface $_element = null;
    private ?ElementInterface $_parent = null;

    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Related';
    }

    public function getType(): string
    {
        //return get_class($this);
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getClass(): string
    {
        return get_class($this);
    }

    public function getElementType(): string
    {
        return Entry::class;
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function getElement(string $type = 'section'): ?ElementInterface
    {
        //if (!$this->_element && !$this->_element[$type]) {
            //if ($this->value) Craft::dd($this->getRealValue('section'));
            //if ($type == 'category') Craft::dd($type);
            if ($this->value){
                if ($type == 'section') {
                    return Craft::$app->getSections()->getSectionById((int) $this->getRealValue('section'));
                }
                if ($type == 'category') {
                    //Craft::dd(Craft::$app->getCategories()->getCategoryById($this->getRealValue('category')));
                    //return Craft::$app->getCategories()->getCategoryById((int) $this->getRealValue('category'));
                    return Category::find()->id($this->getRealValue('category'))->site('*')->one();
                }
            }
        //}
        //if ($type == 'category') Craft::dd($this->_element);
        //return $this->_element[$type];
        return null;
    }

    public function getItemType(): string
    {
        return 'entry';
    }

    public function getRealValue(string $type): mixed
    {
        $value = null;
        if (is_array($this->value)) {
            if (array_key_exists($this->type, $this->value)) {
                $value = $this->value[$this->type];
                if (is_array($value)) {
                    $value = $value[$type];
                    if (is_array($value)) {
                        $value = $value[0];
                    }
                }
            }
        }
        return $value;
    }

    public function getStickyElements(): mixed
    {
        if ($this->sticky) {
            $query = Entry::find();
            $query->id = $this->sticky;
            $query->site('*');
            $query->fixedOrder();
            return $query;
        }

        return null;
    }

    public function getFeaturedItem(): mixed
    {
        if ($this->featured) {
            if ($this->sticky) {
                return $this->stickyElements;
            }

            return $this->getItems(null, true);
        }

        return null;
    }

    public function getParent(): ?ElementInterface
    {
        if (!$this->_parent) {
            $this->_parent = $this->getElement('category') ? $this->getElement('category') : null;
        }

        return $this->_parent;
    }

    public function getItems(mixed $criteria = null, bool $featured = false): mixed
    {
        $query = Entry::find();
        //Craft::dd($this->value);
        $query->sectionId = $this->getElement('section')->id ?? null;

        if($this->getElement('section') && $this->getElement('section')->type == 'structure') {
            $query->level = 1;
        }

        $query->relatedTo = [$this->getElement('category')];

        $query->limit = null;
        if ($this->total) {
            $query->limit = $this->total;
        }
        if ($this->attribute != 'userDefined') {
            $query->orderBy = $this->attribute . ' ' . $this->order;
        } else if ($this->order == 'desc') {
            $query->inReverse = true;
        }
        if (!$featured && $this->featured && !$this->sticky) {
            $query->offset = 1;
        }
        if ($this->sticky) {
            $query->id = array_merge(['not'], $this->sticky);
            $query->limit = null;
            $ids = $query->ids();

            $query = Entry::find();
            if ($this->total) {
                $query->limit = $this->total;
            }
            $sticky = $this->sticky;
            if ($this->featured) {
                unset($sticky[0]);
            }
            $query->id = array_merge($sticky, $ids);
            $query->fixedOrder = true;
        }
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    public function getSourceOptions(array $sources = []): array
    {
        $types = [];
        $criteria = Entry::find();
        if ($sources != '*') {
            $criteria->section = $sources;
        }


        foreach ($criteria->all() as $type)
        {
            $types[] = [
                'label' => $type->title,
                'value' => $type->id,
            ];
        }
        return $types;
    }

    public function setStickyValue(mixed $value): void
    {
        $this->value[$this->type]['section'] = $value;
    }

    public function setAttributesValue(mixed $value): void
    {
        $this->value[$this->type]['section'] = $value;
    }

    public function getSourceAttributes(Model $model): array
    {
        /*if ($group) {
            $group = Craft::$app->getSections()->getSectionByHandle($group);
        } else {*/
            $group = $model->getElement('section') ? $model->getElement('section')->entryTypes[0] : null;
        //}

        $attributes = [];
        if($this->getElement('section')->type == 'structure') {
            $attributes['userDefined'] = 'User Defined';
        }

        $attributes = array_merge($attributes, [
            'title' => 'Title',
            'postDate' => 'Date',
        ]);
        if ($group) {
            foreach ($group->fields as $field)
            {
                //Craft::dump(get_class($field));
                $attributes[$field->handle] = $field->name;
            }
        }
        return $attributes;
    }

    public function getSourceTypes(): array
    {
        $types = [
            '*' => [
                'label' => 'All',
                'value' => '*',
                'handle' => '*',
            ]
        ];
        foreach (Craft::$app->getSections()->getAllSections() as $type)
        {
            $types[$type->id] = [
                'label' => $type->name,
                'value' => $type->id,
                'handle' => $type->handle,
            ];
        }
        return $types;
    }

    public function getInputHtml(Field $field, Model $model, bool $selected = false): string
    {
        $view = Craft::$app->getView();

        if ($model && $model->type == $this->type) {
            //Craft::dd($model);
            $this->value = $model->value ?? null;
        }

        $id = $view->formatInputId($field->handle);
        $namespacedId = $view->namespaceInputId($id);

        $settings = $field->getSettings();
        $elementSettings = $settings['types'][$this->class];
        $sources = $elementSettings['sources'] == "" ? "*" : $elementSettings['sources'];

        $types = $this->sourceTypes;

        //Craft::dd($this->value);

        if ($sources != '*') {
            foreach ($sources as $key => $source)
            {
                $sources[$key] = $types[$source];
            }
        } else {
            $sources = $types;
        }

        $jsonVars = [
            'id' => $id,
            'name' => $field->handle,
            'namespace' => $namespacedId,
            'prefix' => $view->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        $view->registerJs("$('#{$namespacedId}-field').ListingSourceField(" . $jsonVars . ");");
        //if ($this->value) Craft::dd($this->getElement('category'));

        $elements = [];
        if ($this->value) {
            $elements = Category::find()->id($this->getRealValue('category'))->all();
        }

        // Render the input template
        return $view->renderTemplate(
            'listingsource/_components/types/input/_related',
            [
                'section' => [
                    'name' => $field->handle.'[value]['.$this->type.'][section]',
                    'id' => $id.'-'.str_replace("\\","-",$this->type).'-select',
                    'value' => $this->getRealValue('section'),
                    'options' => $sources,
                    'namespacedId' => $namespacedId,
                    'type' => $this->type,
                    'class' => $this->class,
                    'selected' => $selected,
                    'attribute' => $model->attribute ?? null,
                ],
                'category' => [
                    'name' => $field->handle.'[value]['.$this->type.'][category]',
                    'value' => $this->getRealValue('category'),
                    'elements' => $elements,
                    'elementType' => Category::class,
                    'type' => $this->type,
                    'class' => $this->class,
                    'sources' => null,
                    'id' => $id.'-'.str_replace("\\","-",$this->type).'-element',
                    'namespacedId' => $namespacedId,
                    'selected' => $selected,
                    'attribute' => $model->attribute ?? null,
                ],
            ]
        );
    }

    public function getStickyParams(Model $model): array
    {
        $view = Craft::$app->getView();

        return [
            'elementType' => Entry::class,
            'sources' => ['section:'.($model->element->uid ?? 'null')],
            'criteria' => ['level'=>1],
        ];
    }

    public function rules(): array
    {
        $rules = [
            [['value'], 'required']
        ];
        return $rules;
    }

    public function getErrors(?string $attribute = null): array
    {
        $errors = [];
        //Craft::dd($this->value);
        /*if (!$this->getRealValue('section') && (($attribute && $attribute == 'value') || !$attribute)) {
            $errors['value']['section'] = ['Please select a section'];
        }
        if (!$this->getRealValue('category') && (($attribute && $attribute == 'value') || !$attribute)) {
            $errors['value']['category'] = ['Please select a category'];
        }*/
        return $errors;
    }

    public function serializeValue(): array
    {
        //Craft::dd($this->value);
        return [
            'type' => $this->getType(),
            'value' => $this->value,
            'attribute' => $this->attribute,
            'order' => $this->order,
            'total' => $this->total,
            'pagination' => $this->pagination,
            'sticky' => $this->sticky,
            'featured' => $this->featured,
        ];
    }
}
