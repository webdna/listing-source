<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\elements\Entry as CraftChannel;

class Channel extends ElementLink
{
    // Private
    // =========================================================================

	private $_channel;
	private $_entryType;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftChannel::class;
    }

    // Public Methods
	// =========================================================================
	
	public function getItems()
	{
		$criteria = CraftChannel::find()->section($this->getChannel());
		return $criteria;
	}

    public function getChannel()
    {
        if(is_null($this->_channel))
        {
            $this->_channel = Craft::$app->sections->getSectionById((int) explode(':',$this->value)[0]);
        }
        return $this->_channel;
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
        return 'listingsource/types/input/_channel';
    }

}
