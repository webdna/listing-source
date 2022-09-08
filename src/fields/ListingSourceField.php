<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\fields;

use webdna\listingsource\ListingSource;
use webdna\listingsource\assetbundles\field\FieldAsset;
use webdna\listingsource\assetbundles\settings\SettingsAsset;

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
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;
use craft\validators\ArrayValidator;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class ListingSourceField extends Field
{
    // Public Properties
    // =========================================================================

    public array $types = [];
    public array $sources = [];

    // legacy
    public string $selectLinkText = '';
    public bool $allowCustomText = false;
    public string $defaultText = '';
    public bool $allowTarget = false;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('listingsource', 'Listing Source');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        //$rules[] = [['types'], ArrayValidator::class, 'min' => 1, 'tooFew' => Craft::t('listingsource', 'You must select one source type.'), 'skipOnEmpty' => false];
        return $rules;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $sources = false;
        $return = true;
        foreach ($this->types as $key => $value)
        {
            if ($value['enabled']) {
                $sources = true;
                if ($value['sources'] == '') {
                    $this->addError($key, 'Please select at least one source');
                    $return = false;
                }
            }
        }
        if (!$sources) {
            $this->addError('sources', 'Please select a source');
            $return = false;
        }

        return $return;
    }

    public function getElementValidationRules(): array
    {
        return ['validateValue'];
    }

    public function validateValue(ElementInterface $element): void
    {
        $fieldValue = $element->getFieldValue($this->handle);

        if($fieldValue && count($fieldValue->getErrors()))
        {
            $element->addModelErrors($fieldValue, $this->handle);
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof Category ||
            $value instanceof Entry ||
            $value instanceof Group ||
            $value instanceof Products ||
            $value instanceof Section ||
            $value instanceof User ||
            $value instanceof Bundle ||
            $value instanceof Related ||
            $value instanceof Event
        ) {
            return $value;
        }

        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        $model = null;

        if (isset($value['type']) && $value['type'] != '') {

            $value['type'] = str_replace("webdna\\listingsource\\models\\", '', $value['type']);
            $model = $this->getModelByType("webdna\\listingsource\\models\\".$value['type'], $value);
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        //Craft::dd($value);
        if ($value instanceof Category ||
            $value instanceof Entry ||
            $value instanceof Group ||
            $value instanceof Products ||
            $value instanceof Section ||
            $value instanceof User ||
            $value instanceof Bundle ||
            $value instanceof Related ||
            $value instanceof Event
        ) {

                //Craft::dd($value->serializeValue($value, $element));
            return parent::serializeValue($value->serializeValue($value, $element), $element);
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(SettingsAsset::class);

        return $view->renderTemplate(
            'listingsource/_components/fields/settings',
            [
                'field' => $this,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(mixed $value, ElementInterface $element = null): string
    {

        $view = Craft::$app->getView();

        // Register our asset bundle
        $view->registerAssetBundle(FieldAsset::class);

        // Get our id and namespace
        $id = $view->formatInputId($this->handle);
        $namespacedId = $view->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => $view->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        $view->registerJs("$('#{$namespacedId}-field').ListingSourceField(" . $jsonVars . ");");

        // Render the input template
        return $view->renderTemplate(
            'listingsource/_components/fields/input',
            [
                'name' => $this->handle,
                'model' => $value,
                //'type' => $value['type'],
                //'value' => $value['value'],
                'element' => $element,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }

    private function getModelByType(string $type, mixed $value = null): mixed
    {
        if (!$type) {
            return null;
        }
        $pluginsService = Craft::$app->getPlugins();
        $model = new $type();
        if ($value) {
            $model->setAttributes($value, false);
        }
        if ($model->type == 'Products' && (!$pluginsService->isPluginInstalled('commerce') || !$pluginsService->isPluginEnabled('commerce'))) {
            return null;
        }
        if ($model->type == 'Events' && (!$pluginsService->isPluginInstalled('events') || !$pluginsService->isPluginEnabled('events'))) {
            return null;
        }
        return $model;
    }

    public function getAllSourceTypes(): array
    {
        return ListingSource::$plugin->service->getSourceTypes();
    }

    public function getSourceTypes(): array
    {
        $types = [];

        foreach ($this->types as $key => $settings)
        {
            if ($settings['enabled']) {
                $type = $this->getModelByType($key, $settings);
                if ($type) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    public function getSourceTypesAsOptions(): array
    {
        $options = [];
        $options[] = [
            'label' => 'Select source type',
            'value' => '',
        ];

        foreach ($this->getSourceTypes() as $type)
        {
            $options[] = [
                'label' => $type->name,
                'value' => $type->type,
            ];
        }
        return $options;
    }
}
