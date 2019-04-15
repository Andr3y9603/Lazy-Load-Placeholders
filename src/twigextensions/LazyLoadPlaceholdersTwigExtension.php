<?php
/**
 * Lazy Load Placeholders plugin for Craft CMS 3.x
 *
 * Create blurry images from original images + lazy load functionality
 *
 * @link      https://github.com/Andr3y9603
 * @copyright Copyright (c) 2019 Ghiorghiu Andrei
 */

namespace ags\lazyloadplaceholders\twigextensions;

use ags\lazyloadplaceholders\LazyLoadPlaceholders;
use Craft;

/**
 * @author    Ghiorghiu Andrei
 * @package   LazyLoadPlaceholders
 * @since     1.0.0
 */
class LazyLoadPlaceholdersTwigExtension extends \Twig_Extension {
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'LazyLoadPlaceholders';
	}

	/**
	 * @inheritdoc
	 */
	public function getFilters() {
		return [
			new \Twig_SimpleFilter('LLPlaceholder', [$this, 'LLPlaceholder']),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getFunctions() {
		return [
			new \Twig_SimpleFunction('loadLazyLoadScripts', [$this, 'loadLazyLoadScripts']),
		];
	}

	/**
	 * @param null $text
	 *
	 * @return string
	 */
	public function LLPlaceholder($asset = null, $exportBase64 = false) {
		$volumeId = $asset->volumeId;
		$volumesPaths = LazyLoadPlaceholders::$plugin->getSettings()->volumesPaths;
		$volumes = LazyLoadPlaceholders::$plugin->getSettings()->volumes;
		$filename = $asset->filename;

		if (!in_array($volumeId, $volumes)) {
			return false;
		}

		if (strlen(trim($volumesPaths[$volumeId])) === 0) {
			return false;
		}

		$originalImagePath = $volumesPaths[$volumeId];

		if (!$exportBase64) {

			$path = Craft::getAlias('@webroot');
			$placeholderVolume = $path . '/placeholders';
			$filePath = $placeholderVolume . '/' . $filename;
			$rootPath = Craft::getAlias('@web');

			if (file_exists($filePath)) {
				return $rootPath . '/placeholders/' . $filename;
			}

			if (!file_exists($placeholderVolume)) {
				mkdir($placeholderVolume);
			}
		}

		if ($exportBase64) {
			ob_start();
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

		if ($exportBase64) {
			imagejpeg($image);
			$src = ob_get_clean();

			imagedestroy($image);

			return 'data:image/jpeg;base64,' . base64_encode($src);
		} else {
			imagejpeg($image, $placeholderVolume . '/' . $filename);
		}

		imagedestroy($image);

		return $rootPath . '/placeholders/' . $filename;
	}

	public function loadLazyLoadScripts() {
		$file = file_get_contents(__DIR__ . '/../templates/scripts.twig');

		echo $file;
	}
}
