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
use craft\web\Controller;
use craft\elements\Entry;

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
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionSticky()
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

	public function actionAttributes()
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
