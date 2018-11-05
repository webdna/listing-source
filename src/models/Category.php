<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\elements\Category as CraftCategory;

class Category extends ElementLink
{
    // Private
    // =========================================================================

    private $_category;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftCategory::class;
    }

    // Public Methods
	// =========================================================================
	
	public function getItems()
	{
		$criteria = CraftCategory::find()->parentId($this->getCategory()->id);
		return $criteria;
	}

    public function getCategory()
    {
        if(is_null($this->_category))
        {
            $this->_category = Craft::$app->getCategories()->getCategoryById((int) $this->value);
        }
        return $this->_category;
    }

    public function getCategoryHandle()
    {
        if(is_null($this->_category))
        {
            $this->_category = Craft::$app->getCategories()->getCategoryById((int) $this->value);
        }
        return $this->_category->group->handle;
    }
}
