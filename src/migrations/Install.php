<?php
namespace kuriousagency\listingsource\migrations;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\fields\ListingsourceField;
use kuriousagency\listingsource\models\Email;
use kuriousagency\listingsource\models\Phone;
use kuriousagency\listingsource\models\Url;
use kuriousagency\listingsource\models\Entry;
use kuriousagency\listingsource\models\Category;
use kuriousagency\listingsource\models\Asset;
use kuriousagency\listingsource\models\Product;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;

class Install extends Migration
{
    public function safeUp()
    {
        // if ($this->_upgradeFromCraft2()) {
        //     return;
        // }
    }

    private function _upgradeFromCraft2()
    {
        // Locate and remove old listingsource
        $row = (new \craft\db\Query())
            ->select(['id', 'settings'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['fruitlistingsource', 'fruit-link-it', 'fruit-listingsource']])
            ->one();

        if($row)
        {
            $this->delete('{{%plugins}}', ['id' => $row['id']]);
        }

        // Look for any old listingsource fields and update their settings
        $fields = (new \craft\db\Query())
            ->select(['id', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['in', 'type', ['FruitLinkIt']])
            ->all();

        if($fields)
        {
            // Update field settings
            foreach($fields as $field)
            {
                $oldSettings = $field['settings'] ? Json::decode($field['settings']) : null;
                $newSettings = $this->_migrateFieldSettings($oldSettings);

                $this->update('{{%fields}}', [
                    'type' => ListingsourceField::class,
                    'settings' => Json::encode($newSettings)
                ], ['id' => $field['id']]);
            }
        }

        return true;
    }

    private function _migrateFieldSettings($oldSettings)
    {
        if(!$oldSettings)
        {
            return null;
        }

        $listingsourceField = new ListingsourceField();

        $newSettings = $listingsourceField->getSettings();
        $newSettings['defaultText'] = $oldSettings['defaultText'] ?? '';
        $newSettings['allowTarget'] = $oldSettings['allowTarget'] ?? 0;
        $newSettings['allowCustomText'] = $oldSettings['allowCustomText'] ?? 0;

        if($oldSettings['types'])
        {
            foreach ($oldSettings['types'] as $oldType)
            {
                switch ($oldType)
                {
                    case 'email':
                        $newSettings['types'][Email::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'custom':
                        $newSettings['types'][Url::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'tel':
                        $newSettings['types'][Phone::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                        ];
                        break;

                    case 'entry':
                        $newSettings['types'][Entry::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['entrySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['entrySelectionLabel'] ?? '',
                        ];
                        break;

                    case 'category':
                        $newSettings['types'][Category::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['categorySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['categorySelectionLabel'] ?? '',
                        ];
                        break;

                    case 'asset':
                        $newSettings['types'][Asset::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['assetSources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['assetSelectionLabel'] ?? '',
                        ];
                        break;

                    case 'product':
                        $newSettings['types'][Product::class] = [
                            'enabled' => 1,
                            'customLabel' => null,
                            'sources' => $oldSettings['entrySources'] ?? '*',
                            'customSelectionLabel' => $oldSettings['entrySelectionLabel'] ?? '',
                        ];
                        break;
                }
            }
        }


        return $newSettings;
    }

    public function safeDown()
    {
        return true;
    }
}


