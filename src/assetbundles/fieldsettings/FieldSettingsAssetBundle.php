<?php
namespace kuriousagency\listingsource\assetbundles\fieldsettings;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class FieldSettingsAssetBundle extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@kuriousagency/listingsource/assetbundles/fieldsettings/build";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/ListingsourceFieldSettings.js',
        ];

        $this->css = [
            'css/styles.css',
        ];

        parent::init();
    }
}
