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
use craft\elements\User as CraftUser;
use craft\helpers\Json;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class User extends Model
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
        return 'User Group';
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
        return CraftUser::class;
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function getElement(): ?ElementInterface
    {
        if (!$this->_element) {
            if ($this->value){
                $this->_element = Craft::$app->getUserGroups()->getGroupById((int) $this->realValue);
            }
        }
        return $this->_element;
    }

    public function getItemType(): string
    {
        return 'user';
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
            $query = CraftUser::find();
            $query->id = $this->sticky;
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
        $query = CraftUser::find();
        $query->groupId = $this->getElement()->id;

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

            $query = CraftUser::find();
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
        $criteria = CraftUser::find();
        if ($sources != '*') {
            //Craft::dd($sources);
            $criteria->userGroupId = $sources;
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
        $attributes = [
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'email' => 'Email',
            'dateCreated' => 'Date',
        ];
        foreach(Craft::$app->getUser()->getIdentity()->getFieldLayout()->getTabs() as $tab)
        {
            foreach ($tab->getFields() as $field)
            {
                $attributes[$field->handle] = $field->name;
            }
        }

        return $attributes;
    }

    public function getSourceTypes(): array
    {
        $types = [];
        foreach (Craft::$app->getUserGroups()->getAllGroups() as $type)
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

        if ($sources != '*') {
            foreach ($sources as $key => $source)
            {
                $sources[$key] = $types[$source];//'group:'.$source;
            }
        } else {
            $sources = $types;
        }

        //Craft::dd($sources);

        $jsonVars = [
            'id' => $id,
            'name' => $field->handle,
            'namespace' => $namespacedId,
            'prefix' => $view->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        $view->registerJs("$('#{$namespacedId}-field').ListingSourceField(" . $jsonVars . ");");

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
            'elementType' => CraftUser::class,
            'sources' => ['group:'.($model->element->uid ?? 'null')],
            'criteria' => [],
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
        if (!$this->realValue && (($attribute && $attribute == 'value') || !$attribute)) {
            $errors['value'] = ['Please select a user group'];
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
