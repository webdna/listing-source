<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\listingsource\services;

use kuriousagency\listingsource\ListingSource;

use kuriousagency\listingsource\models\Category;
use kuriousagency\listingsource\models\Entry;
use kuriousagency\listingsource\models\Group;
use kuriousagency\listingsource\models\Products;
use kuriousagency\listingsource\models\Section;
use kuriousagency\listingsource\models\User;
use kuriousagency\listingsource\models\Bundle;
use kuriousagency\listingsource\models\Related;

use Craft;
use craft\base\Component;
use craft\helpers\Component as ComponentHelper;

/**
 * @author    Kurious Agency
 * @package   ListingSource
 * @since     2.0.0
 */
class ListingSourceService extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getSourceTypes()
    {
		$pluginsService = Craft::$app->getPlugins();
		$types = [];
		
		$types[] = new Category();
		$types[] = new Entry();
		$types[] = new Related();
		$types[] = new Group();
		if ($pluginsService->isPluginInstalled('commerce') && $pluginsService->isPluginEnabled('commerce')) {
		$types[] = new Products();
		}
		if ($pluginsService->isPluginInstalled('commerce-bundles') && $pluginsService->isPluginEnabled('commerce-bundles')) {
		$types[] = new Bundle();
		}
		$types[] = new Section();
		$types[] = new User();

		return $types;
	}
	
}
