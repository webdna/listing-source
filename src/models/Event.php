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
use craft\elements\Category as CraftCategory;
use verbb\events\elements\Event as CraftEvent;
use craft\helpers\Json;
use craft\helpers\Db;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class Event extends Model
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
        return 'Event Category';
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
        return CraftCategory::class;
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function getElement(): ?ElementInterface
    {
        if (!$this->_element) {
            if ($this->value){
                $this->_element = CraftCategory::find()->id($this->realValue)->site('*')->one();
                //$this->_element = Craft::$app->getCategories()->getCategoryById((int) $this->realValue);
            }
        }
        return $this->_element;
    }

    public function getItemType(): string
    {
        return 'event';
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
            $query = CraftEvent::find();
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
            $this->_parent = $this->getElement() ? $this->getElement()->group : null;
        }

        return $this->_parent;
    }

    public function getItems(mixed $criteria = null, bool $featured = false): mixed
    {
        $currentdate = Db::prepareDateForDb(new \DateTime());
        $query = CraftEvent::find();
        $query->status = ['live','expired'];
        $query->endDate = '>= '.$currentdate;
        $query->relatedTo = $this->getElement()->id;

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

            $query = CraftEvent::find();
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
        $criteria = CraftEvent::find();
        if ($sources != '*') {
            $criteria->group = $sources;
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

    public function getSourceAttributes(): array
    {
        /*if ($group) {
            $group = Craft::$app->getCategories()->getGroupByHandle($group);
        } else {
            $group = $this->getElement() ? $this->getElement()->group : null;
        }*/

        $attributes = [
            //'userDefined' => 'User Defined',
            'title' => 'Title',
            'startDate' => 'Start Date',
            'postDate' => 'Post Date',
        ];
        /*if ($group) {
            foreach ($group->fields as $field)
            {
                $attributes[$field->handle] = $field->name;
            }
        }*/
        return $attributes;
    }

    public function getSourceTypes(): array
    {
        $types = [];
        foreach (Craft::$app->getCategories()->getAllGroups() as $type)
        {
            $types[] = [
                'label' => $type->name,
                'value' => $type->uid,
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

        if ($sources != '*') {
            foreach ($sources as $key => $source)
            {
                $sources[$key] = 'group:'.$source;
            }
        }

        $jsonVars = [
            'id' => $id,
            'name' => $field->handle,
            'namespace' => $namespacedId,
            'prefix' => $view->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        $view->registerJs("$('#{$namespacedId}-field').ListingSourceField(" . $jsonVars . ");");

        // Render the input template
        return $view->renderTemplate(
            'listingsource/_components/types/input/_element',
            [
                'name' => $field->handle.'[value]['.$this->type.']',
                'value' => $this->realValue,
                'elements' => [$this->getElement()],
                'elementType' => CraftCategory::class,
                'type' => $this->type,
                'class' => $this->class,
                'sources' => $sources == '*' ? null : $sources,
                'id' => $id.'-'.str_replace("\\","-",$this->type).'-element',
                'namespacedId' => $namespacedId,
                'selected' => $selected,
                'attribute' => $model->attribute ?? null,
            ]
        );
    }

    public function getStickyParams(Model $model): array
    {
        $view = Craft::$app->getView();

        $params = [
            'elementType' => CraftEvent::class,
            'sources' => null,//['group:'.($this->element->group->uid ?? 'null')],
            'criteria' => ['relatedTo'=>($model->element->id ?? null)],
        ];

        return $params;
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
        if (!$this->realValue && (($attribute && $attribute == 'value') || !$attribute)) {
            $errors['value'] = ['Please select an event category'];
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
