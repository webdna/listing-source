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
use craft\elements\Entry as CraftSection;
use craft\helpers\Json;
use craft\models\Section as ModelsSection;
use craft\validators\ArrayValidator;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class Section extends Model
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

    private ?ModelsSection $_element = null;
    private ?ElementInterface $_parent = null;

    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Section';
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
        return CraftSection::class;
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function getElement(): ?ModelsSection
    {
        if (!$this->_element) {
            if ($this->value){
                $this->_element = Craft::$app->getSections()->getSectionById((int) $this->realValue);
                //$this->_element = CraftSection::find()->id($this->realValue)->site('*')->one();
            }
        }
        return $this->_element;
    }

    public function getItemType(): string
    {
        return $this->element->handle;
    }

    public function getRealValue(): mixed
    {
        if (is_array($this->value)) {
            if (array_key_exists($this->type, $this->value)) {
                $this->value = $this->value[$this->type];
                if (is_array($this->value)) {
                    $this->value = $this->value[0];
                }
            } else {
                $this->value = null;
            }
        }
        return $this->value;
    }

    public function getStickyElements(): mixed
    {
        if ($this->sticky) {
            $query = CraftSection::find();
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
            $this->_parent = $this->getElement() ? $this->getElement() : null;
        }

        return $this->_parent;
    }

    public function getItems(mixed $criteria = null, bool $featured = false): mixed
    {
        $query = CraftSection::find();
        $query->sectionId = $this->getElement()->id;

        if($this->getElement()->type == 'structure') {
            $query->level = 1;
        }

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

            $query = CraftSection::find();
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
        $criteria = CraftSection::find();
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
        $this->value = $value;
    }

    public function setAttributesValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getSourceAttributes(Model $model): array
    {
        /*if ($group) {
            $group = Craft::$app->getSections()->getSectionByHandle($group);
        } else {*/
            $group = $model->getElement() ? $model->getElement()->entryTypes[0] : null;
        //}

        $attributes = [];
        if($this->getElement()->type == 'structure') {
            $attributes['userDefined'] = 'User Defined';
        }

        $attributes = array_merge($attributes, [
            'title' => 'Title',
            'postDate' => 'Date',
        ]);
        if ($group) {
            foreach ($group->fieldLayout->customFields as $field)
            {
                //Craft::dump(get_class($field));
                $attributes[$field->handle] = $field->name;
            }
        }
        return $attributes;
    }

    public function getSourceTypes(): array
    {
        $types = [];
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
            $this->value = $model->value ?? null;
        }

        $id = $view->formatInputId($field->handle);
        $namespacedId = $view->namespaceInputId($id);

        $settings = $field->getSettings();
        $elementSettings = $settings['types'][$this->class];
        $sources = $elementSettings['sources'] == "" ? "*" : $elementSettings['sources'];

        $types = $this->sourceTypes;

        //Craft::dd($types);

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
        //Craft::dump($model);
        // Render the input template
        return $view->renderTemplate(
            'listingsource/_components/types/input/_group',
            [
                'name' => $field->handle.'[value]['.$this->type.']',
                'id' => $id.'-'.str_replace("\\","-",$this->type).'-select',
                'value' => $this->realValue,
                'options' => $sources,
                'namespacedId' => $namespacedId,
                'type' => $this->type,
                'class' => $this->class,
                'selected' => $selected,
                'attribute' => $model->attribute ?? null,
            ]
        );
    }

    public function getStickyParams(Model $model): array
    {
        $view = Craft::$app->getView();

        return [
            'elementType' => CraftSection::class,
            'sources' => ['section:'.($model->element->uid ?? 'null')],
            'criteria' => $this->getElement()->type == 'structure' ? ['level'=>1] : [],
        ];
    }

    public function rules(): array
    {
        $rules = [
            [['value'], 'required']
        ];
        return $rules;
    }

    public function getErrors($attribute = null): array
    {
        $errors = [];
        if (!$this->realValue && (($attribute && $attribute == 'value') || !$attribute)) {
            $errors['value'] = ['Please select a section'];
        }
        return $errors;
    }

    public function serializeValue(): array
    {
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
