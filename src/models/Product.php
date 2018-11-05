<?php
namespace kuriousagency\listingsource\models;

use Craft;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\base\ElementLink;

use craft\commerce\Plugin as CraftCommercePlugin;
use craft\commerce\elements\Product as CraftCommerceProduct;
use craft\commerce\elements\Variant as CraftCommerceVariant;

class Product extends ElementLink
{
    // Private
    // =========================================================================

    private $_product;

    // Static
    // =========================================================================

    public static function elementType()
    {
        return CraftCommerceProduct::class;
    }

    // Public Methods
	// =========================================================================
	
	public function getItems()
	{
		if ($this->getProduct()) {
			$criteria = CraftCommerceVariant::find()->productId($this->getProduct()->id);
		} else {
			$ids = [];
			foreach ($this->sources as $source)
			{
				$a = explode(':', $source);
				if ($a[0] == 'productType') {
					$ids[] = $a[1];
				}
			}
			//Craft::dd($ids);
			$criteria = CraftCommerceProduct::find()->typeId($ids);
		}
		
		return $criteria;
	}

    public function getProduct()
    {
        if(is_null($this->_product))
        {
            $this->_product = CraftCommercePlugin::getInstance()->getProducts()->getProductById((int) $this->value, $this->ownerElement->siteId ?? null);
        }
        return $this->_product;
    }
}
