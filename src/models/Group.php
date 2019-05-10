<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\listingsource\models;

use kuriousagency\listingsource\ListingSource;

use Craft;
use craft\base\Model;
use craft\base\ElementInterface;
use craft\elements\Category as CraftCategory;
use craft\helpers\Json;
use craft\validators\ArrayValidator;

/**
 * @author    Kurious Agency
 * @package   ListingSource
 * @since     2.0.0
 */
class Group extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
	public $sources;
	public $value;
	public $attribute;
	public $order;
	public $total;
	public $pagination = false;
	public $sticky;
	public $featured;

	private $_element;
	private $_parent;

    // Public Methods
    // =========================================================================

	public function getName()
	{
		return 'Category Group';
	}
	
	public function getType()
	{
		//return get_class($this);
		return (new \ReflectionClass($this))->getShortName();
	}

	public function getClass()
	{
		return get_class($this);
	}

	public function getElementType()
	{
		return CraftCategory::class;
	}

	public function hasSettings()
	{
		return true;
	}

	public function getElement()
	{
		if (!$this->_element) {
			if ($this->value){
				$this->_element = Craft::$app->getCategories()->getGroupById((int) $this->realValue);
			}
		}
		return $this->_element;
	}

	public function getItemType()
	{
		return $this->element->handle;
	}

	public function getRealValue()
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

	public function getStickyElements()
	{
		if ($this->sticky) {
			$query = CraftCategory::find();
			$query->id = $this->sticky;
			return $query;
		}

		return null;
	}

	public function getFeaturedItem()
	{
		if ($this->featured) {
			if ($this->sticky) {
				return $this->stickyElements;
			}

			return $this->getItems(null, true);
		}

		return null;
	}

	public function getParent()
	{
		if (!$this->_parent) {
			$this->_parent = $this->getElement() ? $this->getElement() : null;
		}

		return $this->_parent;
	}

	public function getItems($criteria = null, $featured=false)
	{
		$query = CraftCategory::find();
		$query->groupId = $this->getElement()->id;
		$query->level = 1;
		
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

			$query = CraftCategory::find();
			if ($this->total) {
				$query->limit = $this->total;
			}
			$sticky = $this->sticky;
			unset($sticky[0]);
			$query->id = array_merge($sticky, $ids);
			$query->fixedOrder = true;
		}
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	public function getSourceOptions($sources=[])
	{
		$types = [];
		$criteria = CraftCategory::find();
		if ($sources != '*') {
			//Craft::dd($sources);
			$criteria->groupId = $sources;
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

	public function getSourceAttributes($model)
	{
		$group = $model->getElement() ? $model->getElement() : null;
		
		$attributes = [
			'userDefined' => 'User Defined',
			'title' => 'Title',
			'dateCreated' => 'Date',
		];
		if ($group) {
			foreach ($group->fields as $field)
			{
				$attributes[$field->handle] = $field->name;
			}
		}
		return $attributes;
	}

	public function getSourceTypes()
	{
		$types = [];
		foreach (Craft::$app->getCategories()->getAllGroups() as $type)
		{
			$types[$type->id] = [
				'label' => $type->name,
				'value' => $type->id,
				'handle' => $type->handle,
			];
		}
		return $types;
	}

	public function getInputHtml($field, $model, $selected=false): string
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

	public function getStickyParams($model)
	{
		$view = Craft::$app->getView();

		return [
			'elementType' => CraftCategory::class,
			'sources' => ['group:'.($model->element->uid ?? 'null')],
			'criteria' => ['level'=>1],
		];
	}

	public function rules()
	{
		$rules = [
            [['value'], 'required']
        ];
        return $rules;
	}

	public function getErrors($attribute = NULL)
	{
		$errors = [];
		if (!$this->realValue && (($attribute && $attribute == 'value') || !$attribute)) {
			$errors['value'] = ['Please select a category group'];
		}
		return $errors;
	}

	public function serializeValue($value, ElementInterface $element = null)
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
