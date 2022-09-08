<?php
/**
 * Listing Source plugin for Craft CMS 3.x
 *
 * listing entries, categories, etc.
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2019 webdna
 */

namespace webdna\listingsource\controllers;

use webdna\listingsource\ListingSource;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use craft\web\Response;

/**
 * @author    webdna
 * @package   ListingSource
 * @since     2.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array|int Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected bool|array|int $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionSticky(): ?Response
    {
        $request = Craft::$app->getRequest();
        $handle = $request->getRequiredBodyParam('handle');
        $type = $request->getRequiredBodyParam('type');
        $value = $request->getRequiredBodyParam('value');

        if ($value == '*') {
            return $this->asJson([
                'elementType' => Entry::class,
                'sources' => ['*'],
                'criteria' => ['level'=>1],
            ]);
        }

        $model = new $type();
        $model->setStickyValue($value);

        return $this->asJson($model->getStickyParams($model));
    }

    public function actionAttributes(): ?Response
    {
        $request = Craft::$app->getRequest();
        $type = $request->getRequiredBodyParam('type');
        $value = $request->getRequiredBodyParam('value');

        if ($value == '*') {
            return $this->asJson([
                'title' => 'Title',
                'postDate' => 'Date',
            ]);
        }

        $model = new $type();
        $model->setAttributesValue($value);

        return $this->asJson($model->getSourceAttributes($model));
    }

}
