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
use craft\base\Model;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\Category;
use craft\helpers\Json;
use craft\validators\ArrayValidator;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class Related extends Model
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
		return 'Related';
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
		return Entry::class;
	}

	public function hasSettings()
	{
		return true;
	}

	public function getElement($type='section')
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

	public function getItemType()
	{
		return 'entry';//$this->element->handle;
	}

	public function getRealValue($type)
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

	public function getStickyElements()
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
			$this->_parent = $this->getElement('category') ? $this->getElement('category') : null;
		}

		return $this->_parent;
	}

	public function getItems($criteria = null, $featured=false)
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

	public function getSourceOptions($sources=[])
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

	public function setStickyValue($value)
	{
		$this->value[$this->type]['section'] = $value;
	}

	public function setAttributesValue($value)
	{
		$this->value[$this->type]['section'] = $value;
	}

	public function getSourceAttributes($model)
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

	public function getSourceTypes()
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

	public function getInputHtml($field, $model, $selected=false): string
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

	public function getStickyParams($model)
	{
		$view = Craft::$app->getView();

		return [
			'elementType' => Entry::class,
			'sources' => ['section:'.($model->element->uid ?? 'null')],
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
		//Craft::dd($this->value);
		/*if (!$this->getRealValue('section') && (($attribute && $attribute == 'value') || !$attribute)) {
			$errors['value']['section'] = ['Please select a section'];
		}
		if (!$this->getRealValue('category') && (($attribute && $attribute == 'value') || !$attribute)) {
			$errors['value']['category'] = ['Please select a category'];
		}*/
		return $errors;
	}

	public function serializeValue($value, ElementInterface $element = null)
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
