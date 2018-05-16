<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\elements\Category as CraftCategory;

class Group extends ElementLink
{
    // Private
    // =========================================================================

    private $_group;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftCategory::class;
    }

    // Public Methods
    // =========================================================================

    public function getGroup()
    {
        if(is_null($this->_group))
        {
            $this->_group = Craft::$app->categories->getGroupById((int) $this->value);
        }
        return $this->_group;
    }

    public static function inputTemplatePath(): string
    {
        return 'listingsource/types/input/_select';
    }

}
