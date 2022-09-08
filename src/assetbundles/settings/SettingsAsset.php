<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\assetbundles\settings;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class SettingsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = "@webdna/listingsource/assetbundles/settings/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/ListingSourceFieldSettings.js',
        ];

        $this->css = [
            'css/ListingSourceFieldSettings.css',
        ];

        parent::init();
    }
}
