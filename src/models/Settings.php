<?php
/**
 * Google  reCAPTCHA v3 plugin for Craft CMS 3.x
 *
 * reCAPTCHA v3 returns a score for each request without user friction
 *
 * @link      https://github.com/Andr3y9603
 * @copyright Copyright (c) 2019 Ghiorghiu Andrei
 */

namespace ags\lazyloadplaceholders\models;

use ags\lazyloadplaceholders\LazyLoadPlaceholders;
use Craft;
use craft\base\Model;

/**
 * @author    Ghiorghiu Andrei
 * @package   GoogleRecaptchaV3
 * @since     1.0.0
 */
class Settings extends Model {
	// Public Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $volumes = [];
	public $volumesPaths = '';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
		];
	}
}
