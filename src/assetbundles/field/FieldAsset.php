<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\assetbundles\field;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class FieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@webdna/listingsource/assetbundles/field/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/ListingSourceField.js',
        ];

        $this->css = [
            'css/ListingSourceField.css',
        ];

        parent::init();
    }
}
