<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\elements\Entry as CraftSection;

class Section extends ElementLink
{
    // Private
    // =========================================================================

	private $_section;
	private $_entryType;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftSection::class;
    }

    // Public Methods
	// =========================================================================
	
	public function getItems()
	{
		$criteria = CraftSection::find()->section($this->getSection());
		if ($type = $this->getEntryType()) {
			$criteria->type($type);
		}
		return $criteria;
	}

    public function getSection()
    {
        if(is_null($this->_section))
        {
            $this->_section = Craft::$app->sections->getSectionById((int) $this->value);
        }
        return $this->_section;
	}
	
	public function getEntryType()
	{
		if(is_null($this->_entryType))
        {
            $this->_entryType = Craft::$app->sections->getEntryTypeById((int) explode(':',$this->value)[1]);
        }
        return $this->_entryType;
	}

    public static function inputTemplatePath(): string
    {
        return 'listingsource/types/input/_section';
    }

}
