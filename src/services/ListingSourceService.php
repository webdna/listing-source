<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\services;

use webdna\listingsource\ListingSource;

use webdna\listingsource\models\Category;
use webdna\listingsource\models\Entry;
use webdna\listingsource\models\Group;
use webdna\listingsource\models\Products;
use webdna\listingsource\models\Section;
use webdna\listingsource\models\User;
use webdna\listingsource\models\Bundle;
use webdna\listingsource\models\Related;
use webdna\listingsource\models\Event;

use Craft;
use craft\base\Component;
use craft\helpers\Component as ComponentHelper;

/**
 * @author    webdna
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
    public function getSourceTypes(): array
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
        if ($pluginsService->isPluginInstalled('events') && $pluginsService->isPluginEnabled('events')) {
        $types[] = new Event();
        }
        $types[] = new Section();
        $types[] = new User();

        return $types;
    }

}
