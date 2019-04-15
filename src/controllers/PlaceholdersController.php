<?php

namespace ags\lazyloadplaceholders\controllers;

use ags\lazyloadplaceholders\LazyLoadPlaceholders;
use Craft;
use craft\web\Controller;

class PlaceholdersController extends Controller {

	// Protected Properties
	// =========================================================================

	/**
	 * @var    bool|array Allows anonymous access to this controller's actions.
	 *         The actions must be in 'kebab-case'
	 * @access protected
	 */
	protected $allowAnonymous = ['index'];

	public function actionIndex() {

		$response = 'da';
		return json_encode(['da' => 123, 'nu' => 33]);

	}

	public function actionGenerateAll() {

		$volumeId = $_GET['volumeId'];
		$volumesPaths = LazyLoadPlaceholders::$plugin->getSettings()->volumesPaths;
		$volumes = LazyLoadPlaceholders::$plugin->getSettings()->volumes;
		$filename = $_GET['filename'];

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

		return true;
	}

	public function actionGetFiles() {
		$volumesPaths = LazyLoadPlaceholders::$plugin->getSettings()->volumesPaths;
		$volumes = LazyLoadPlaceholders::$plugin->getSettings()->volumes;

		$volumes = array_filter($volumes);

		$groupNr = 5;
		$nr = 0;

		$filesGroups = [];
		for ($i = 0; $i < count($volumes); $i++) {
			$volumeId = $volumes[$i];
			$path = $volumesPaths[$volumeId];

			$files = array_filter(scandir($path), function ($v, $k) {
				return $v !== '.' && $v !== '..' && $v !== '.DS_Store';
			}, ARRAY_FILTER_USE_BOTH);

			sort($files);

			for ($j = 0; $j < count($files); $j++) {
				$file = $files[$j];

				if (empty($filesGroups[$nr])) {
					$filesGroups[$nr] = [];
				}

				$filesGroups[$nr][] = [
					'filename' => $file,
					'volumeId' => $volumeId,
				];

				if (($j + 1) % $groupNr === 0) {
					$nr++;
				}

			}

			return json_encode($filesGroups);
		}

	}
}