<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\elements\Entry as CraftEntry;

class Entry extends ElementLink
{
    // Private
    // =========================================================================

    private $_entry;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftEntry::class;
    }

    // Public Methods
	// =========================================================================
	
	public function getItems()
	{
		$criteria = CraftEntry::find()->parentId($this->getEntry()->id);
		return $criteria;
	}

    public function getEntry()
    {
        if(is_null($this->_entry))
        {
            $this->_entry = Craft::$app->getEntries()->getEntryById((int) $this->value);
        }
        return $this->_entry;
    }
}
