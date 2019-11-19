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
//use craft\commerce\elements\Product as CraftProduct;
use kuriousagency\commerce\bundles\elements\Bundle as CraftBundle;
use craft\helpers\Json;
use craft\validators\ArrayValidator;

/**
 * @author    Kurious Agency
 * @package   ListingSource
 * @since     2.0.0
 */
class Bundle extends Model
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
		return 'Bundle Category';
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
				$this->_element = CraftBundle::find()->id($this->realValue)->site('*')->one();
				//$this->_element = Craft::$app->getCategories()->getCategoryById((int) $this->realValue);
			}
		}
		return $this->_element;
	}

	public function getItemType()
	{
		return 'bundle';
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
			$query = CraftBundle::find();
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
			$this->_parent = $this->getElement() ? $this->getElement()->group : null;
		}

		return $this->_parent;
	}

	public function getItems($criteria = null, $featured=false)
	{
		$query = CraftBundle::find();
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

			$query = CraftBundle::find();
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
		$criteria = CraftBundle::find();
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

	public function setStickyValue($value)
	{
		$this->value[$this->type] = $value;
	}

	public function setAttributesValue($value)
	{
		$this->value[$this->type] = $value;
	}

	public function getSourceAttributes($model)
	{
		/*if ($group) {
			$group = Craft::$app->getCategories()->getGroupByHandle($group);
		} else {
			$group = $this->getElement() ? $this->getElement()->group : null;
		}*/
		
		$attributes = [
			//'userDefined' => 'User Defined',
			'title' => 'Title',
			'postDate' => 'Date',
			'price' => 'Price',
		];
		/*if ($group) {
			foreach ($group->fields as $field)
			{
				$attributes[$field->handle] = $field->name;
			}
		}*/
		return $attributes;
	}

	public function getSourceTypes()
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

	public function getStickyParams($model)
	{
		$view = Craft::$app->getView();

		$params = [
			'elementType' => CraftBundle::class,
			'sources' => null,//['group:'.($this->element->group->uid ?? 'null')],
			'criteria' => ['relatedTo'=>($model->element->id ?? null)],
		];

		return $params;
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
			$errors['value'] = ['Please select a bundle category'];
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
