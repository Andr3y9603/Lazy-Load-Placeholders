<?php
/**
 * Lazy Load Placeholders plugin for Craft CMS 3.x
 *
 * Create blurry images from original images + lazy load functionality
 *
 * @link      https://github.com/Andr3y9603
 * @copyright Copyright (c) 2019 Ghiorghiu Andrei
 */

namespace ags\lazyloadplaceholders;

use ags\lazyloadplaceholders\models\Settings;
use ags\lazyloadplaceholders\services\Functions as FunctionsService;
use ags\lazyloadplaceholders\twigextensions\LazyLoadPlaceholdersTwigExtension;
use Craft;
use craft\base\Plugin;
use craft\events\ElementEvent;
use craft\events\PluginEvent;
use craft\services\Elements;
use craft\services\Path;
use craft\services\Plugins;
use yii\base\Event;

/**
 * Class LazyLoadPlaceholders
 *
 * @author    Ghiorghiu Andrei
 * @package   LazyLoadPlaceholders
 * @since     1.0.0
 *
 * @property  FunctionsService $functions
 */
class LazyLoadPlaceholders extends Plugin {
	// Static Properties
	// =========================================================================

	/**
	 * @var LazyLoadPlaceholders
	 */
	public static $plugin;

	public $hasCpSettings = true;

	// Public Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $schemaVersion = '1.0.0';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();
		self::$plugin = $this;

		Craft::$app->view->registerTwigExtension(new LazyLoadPlaceholdersTwigExtension());

		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_INSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
				}
			}
		);

		Event::on(
			Elements::class,
			Elements::EVENT_AFTER_SAVE_ELEMENT,
			function (ElementEvent $event) {

				if (!$event->element instanceof craft\elements\Asset) {
					return $event;
				}

				$volumeId = $event->element->volumeId;
				$volumesPaths = LazyLoadPlaceholders::$plugin->getSettings()->volumesPaths;
				$volumes = LazyLoadPlaceholders::$plugin->getSettings()->volumes;
				$filename = $event->element->filename;

				if (!in_array($volumeId, $volumes)) {
					return false;
				}

				if (strlen(trim($volumesPaths[$volumeId])) === 0) {
					return false;
				}

				$originalImagePath = $volumesPaths[$volumeId];

				$path = Craft::getAlias('@webroot');
				$placeholderVolume = $path . '/placeholders';
				$filePath = $placeholderVolume . '/' . $filename;
				$rootPath = Craft::getAlias('@web');

				if (file_exists($filePath)) {
					return true;
				}

				if (!file_exists($placeholderVolume)) {
					mkdir($placeholderVolume);
				}

				$file = $originalImagePath . '/' . $filename;

				$type = exif_imagetype($file);

				if ($type === IMAGETYPE_JPEG) {
					$image = imagecreatefromjpeg($file);
				}
				if ($type === IMAGETYPE_PNG) {
					$image = imagecreatefrompng($file);
				}

				list($w, $h) = getimagesize($file);

				$size = array('sm' => array('w' => intval($w / 5), 'h' => intval($h / 5)),
					'md' => array('w' => intval($w / 2), 'h' => intval($h / 2)),
				);

				$sm = imagecreatetruecolor($size['sm']['w'], $size['sm']['h']);
				imagecopyresampled($sm, $image, 0, 0, 0, 0, $size['sm']['w'], $size['sm']['h'], $w, $h);

				for ($x = 1; $x <= 40; $x++) {
					imagefilter($sm, IMG_FILTER_GAUSSIAN_BLUR, 999);
				}

				imagefilter($sm, IMG_FILTER_SMOOTH, 99);
				imagefilter($sm, IMG_FILTER_BRIGHTNESS, 10);

				$md = imagecreatetruecolor($size['md']['w'], $size['md']['h']);
				imagecopyresampled($md, $sm, 0, 0, 0, 0, $size['md']['w'], $size['md']['h'], $size['sm']['w'], $size['sm']['h']);
				imagedestroy($sm);

				for ($x = 1; $x <= 25; $x++) {
					imagefilter($md, IMG_FILTER_GAUSSIAN_BLUR, 999);
				}

				imagefilter($md, IMG_FILTER_SMOOTH, 99);
				imagefilter($md, IMG_FILTER_BRIGHTNESS, 10);

				imagecopyresampled($image, $md, 0, 0, 0, 0, $w, $h, $size['md']['w'], $size['md']['h']);
				imagedestroy($md);

				imagejpeg($image, $placeholderVolume . '/' . $filename);

				imagedestroy($image);

			}
		);

		Craft::info(
			Craft::t(
				'lazy-load-placeholders',
				'{name} plugin loaded',
				['name' => $this->name]
			),
			__METHOD__
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function createSettingsModel() {
		return new Settings();
	}

	/**
	 * @inheritdoc
	 */
	protected function settingsHtml(): string{
		$volumes = Craft::$app->getVolumes()->publicVolumes;

		$asda = Craft::getAlias('@web');

		$volumesMap = array_map(function ($item) {
			preg_match('/@[a-zA-Z]*/', $item->path, $matches, PREG_UNMATCHED_AS_NULL);

			if (count($matches) !== 0) {
				$path = Craft::getAlias($matches[0]);
				$path = preg_replace('/@[a-zA-Z]*/', $path, $item->path);
			} else {
				$path = Craft::getAlias('@webroot');
				$path .= '/' . $item->path;
			}

			return [
				'name' => $item->name,
				'path' => $path,
				'handle' => $item->handle,
				'id' => $item->id,
				'uid' => $item->uid,
			];
		}, $volumes);

		return Craft::$app->view->renderTemplate(
			'lazy-load-placeholders/settings',
			[
				'settings' => $this->getSettings(),
				'volumes' => $volumesMap,
			]
		);
	}

}
