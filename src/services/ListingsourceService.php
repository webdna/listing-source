<?php
namespace kuriousagency\listingsource\services;

use kuriousagency\listingsource\Listingsource;
use kuriousagency\listingsource\events\RegisterLinkTypesEvent;

// use kuriousagency\listingsource\models\Phone;
// use kuriousagency\listingsource\models\Url;
// use kuriousagency\listingsource\models\Email;
// use kuriousagency\listingsource\models\Asset;
use kuriousagency\listingsource\models\Entry;
use kuriousagency\listingsource\models\Category;
// use kuriousagency\listingsource\models\User;
// use kuriousagency\listingsource\models\Product;
// use kuriousagency\listingsource\models\Twitter;
// use kuriousagency\listingsource\models\Facebook;
// use kuriousagency\listingsource\models\LinkedIn;
// use kuriousagency\listingsource\models\Instagram;

use kuriousagency\listingsource\models\Channel; 
use kuriousagency\listingsource\models\Group; 

use Craft;
use craft\base\Component;
use craft\helpers\Component as ComponentHelper;

class ListingsourceService extends Component
{
    // Constants
    // =========================================================================

    const EVENT_REGISTER_LISTINGSOURCE_FIELD_TYPES = 'registerListingsourceFieldTypes';

    // Public Methods
    // =========================================================================

    public function getAvailableLinkTypes()
    {
        $linkTypes = [];

        // Basic link types
        // $linkTypes[] = new Email();
        // $linkTypes[] = new Phone();
        // $linkTypes[] = new Url();

        // Social link types
        // $linkTypes[] = new Twitter();
        // $linkTypes[] = new Facebook();
        // $linkTypes[] = new Instagram();
        // $linkTypes[] = new LinkedIn();

        // Element link types
        $linkTypes[] = new Entry();
        $linkTypes[] = new Category();       
        // $linkTypes[] = new Asset();
        // $linkTypes[] = new User();

        // Product link
        // if(Craft::$app->getPlugins()->getPlugin('commerce'))
        // {
        //     $linkTypes[] = new Product();
        // }

        // MJ added 
        $linkTypes[] = new Channel();
        $linkTypes[] = new Group();

        // Third Party
        $event = new RegisterLinkTypesEvent([
            'types' => $linkTypes
        ]);
        $this->trigger(self::EVENT_REGISTER_LISTINGSOURCE_FIELD_TYPES, $event);
        return $event->types;
    }

    // Thrid Party Field Types
    //
    // public function getAllFieldTypes(): array
    // {
    //     $fieldTypes = [
    //         AssetsField::class,
    //         CategoriesField::class,
    //         CheckboxesField::class,
    //         ColorField::class,
    //         DateField::class,
    //         DropdownField::class,
    //         EmailField::class,
    //         EntriesField::class,
    //         LightswitchField::class,
    //         MatrixField::class,
    //         MultiSelectField::class,
    //         NumberField::class,
    //         PlainTextField::class,
    //         RadioButtonsField::class,
    //         TableField::class,
    //         TagsField::class,
    //         UrlField::class,
    //         UsersField::class,
    //     ];

    //     $event = new RegisterComponentTypesEvent([
    //         'types' => $fieldTypes
    //     ]);
    //     $this->trigger(self::EVENT_REGISTER_FIELD_TYPES, $event);

    //     return $event->types;
    // }



    public function getSourceOptions($elementType,$sourceType): array
    {
        // $sources = Craft::$app->getElementIndexes()->getSources($elementType, 'modal');

        $options = [];
        $optionNames = [];

        if($elementType == 'craft\elements\Entry') {
            return $this->_getSections($sourceType);
        } else {
            return $this->_getGroups($elementType);
        }
    }


    private function _getSections($sourceType)
    {
        $options = [];
        $optionNames = [];

        $type = $sourceType == "entry" ? "structure" : $sourceType;

        $channels = Craft::$app->sections->getAllSections();
        
        foreach ($channels as $source) {

            // Make sure it's not a heading
            if ($source->type == $type) {
                $options[] = [
                    'label' => $source->name,
                    'value' => $source->id
                ];
                $optionNames[] = $source->name;
            }
        }

        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }


    private function _getGroups($elementType) {

        $sources = Craft::$app->getElementIndexes()->getSources($elementType, 'modal');
        $options = [];
        $optionNames = [];

        foreach ($sources as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key']
                ];
                $optionNames[] = $source['label'];
            }
        }

        // Sort alphabetically
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }

    /*

    public function getSourceOptions($elementType): array
    {
        $sources = Craft::$app->getElementIndexes()->getSources($elementType, 'modal');
        $options = [];
        $optionNames = [];

        foreach ($sources as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key']
                ];
                $optionNames[] = $source['label'];
            }
        }

        // Sort alphabetically
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }
    
    */


}
