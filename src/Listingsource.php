<?php
namespace kuriousagency\listingsource;

use kuriousagency\listingsource\fields\ListingsourceField;
use kuriousagency\listingsource\services\ListingsourceService;

use Craft;
use craft\base\Plugin;
use yii\base\Event;

use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;

use craft\services\Plugins;
use craft\services\Fields;

class Listingsource extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Methods
    // =========================================================================

    public $schemaVersion = '1.0.7.1';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'service' => ListingsourceService::class,
        ]);

        Event::on(Fields::className(), Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = ListingsourceField::class;
        });

        Event::on(Plugins::className(), Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this)
            {
            }
        });

        Craft::info(
            Craft::t('listingsource', '{name} plugin loaded', [
                'name' => $this->name
            ]),
            __METHOD__
        );
    }
}
