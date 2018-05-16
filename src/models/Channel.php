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

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftChannel::class;
    }

    // Public Methods
    // =========================================================================

    public function getChannel()
    {
        if(is_null($this->_channel))
        {
            $this->_channel = Craft::$app->sections()->getSectionById((int) $this->value);
        }
        return $this->_channel;
    }

    public static function inputTemplatePath(): string
    {
        return 'listingsource/types/input/_select';
    }

}
