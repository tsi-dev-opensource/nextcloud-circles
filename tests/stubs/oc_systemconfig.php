<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\IConfig;

/**
 * Class which provides access to the system config values stored in config.php
 * Internal class for bootstrap only.
 * fixes cyclic DI: AllConfig needs AppConfig needs Database needs AllConfig
 */
class SystemConfig {
	/** @var array */
	protected $sensitiveValues = [
		'instanceid' => true,
		'datadirectory' => true,
		'dbname' => true,
		'dbhost' => true,
		'dbpassword' => true,
		'dbuser' => true,
		'dbreplica' => true,
		'activity_dbname' => true,
		'activity_dbhost' => true,
		'activity_dbpassword' => true,
		'activity_dbuser' => true,
		'mail_from_address' => true,
		'mail_domain' => true,
		'mail_smtphost' => true,
		'mail_smtpname' => true,
		'mail_smtppassword' => true,
		'passwordsalt' => true,
		'secret' => true,
		'updater.secret' => true,
		'updater.server.url' => true,
		'trusted_proxies' => true,
		'preview_imaginary_url' => true,
		'preview_imaginary_key' => true,
		'proxyuserpwd' => true,
		'sentry.dsn' => true,
		'sentry.public-dsn' => true,
		'zammad.download.secret' => true,
		'zammad.portal.secret' => true,
		'zammad.secret' => true,
		'github.client_id' => true,
		'github.client_secret' => true,
		'log.condition' => [
			'shared_secret' => true,
			'matches' => true,
		],
		'license-key' => true,
		'redis' => [
			'host' => true,
			'password' => true,
		],
		'redis.cluster' => [
			'seeds' => true,
			'password' => true,
		],
		'objectstore' => [
			'arguments' => [
				// Legacy Swift (https://github.com/nextcloud/server/pull/17696#discussion_r341302207)
				'options' => [
					'credentials' => [
						'key' => true,
						'secret' => true,
					]
				],
				// S3
				'key' => true,
				'secret' => true,
				'sse_c_key' => true,
				// Swift v2
				'username' => true,
				'password' => true,
				// Swift v3
				'user' => [
					'name' => true,
					'password' => true,
				],
			],
		],
		'objectstore_multibucket' => [
			'arguments' => [
				'options' => [
					'credentials' => [
						'key' => true,
						'secret' => true,
					]
				],
				// S3
				'key' => true,
				'secret' => true,
				// Swift v2
				'username' => true,
				'password' => true,
				// Swift v3
				'user' => [
					'name' => true,
					'password' => true,
				],
			],
		],
		'onlyoffice' => [
			'jwt_secret' => true,
		],
		'PASS' => true,
	];

	public function __construct(
		private Config $config,
	) {
	}

	/**
	 * Since system config is admin controlled, we can tell psalm to ignore any taint
	 *
	 * @psalm-taint-escape sql
	 * @psalm-taint-escape html
	 * @psalm-taint-escape ldap
	 * @psalm-taint-escape callable
	 * @psalm-taint-escape file
	 * @psalm-taint-escape ssrf
	 * @psalm-taint-escape cookie
	 * @psalm-taint-escape header
	 * @psalm-taint-escape has_quotes
	 * @psalm-pure
	 */
	public static function trustSystemConfig(mixed $value): mixed
 {
 }

	/**
	 * Lists all available config keys
	 * @return array an array of key names
	 */
	public function getKeys()
 {
 }

	/**
	 * Sets a new system wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param mixed $value the value that should be stored
	 */
	public function setValue($key, $value)
 {
 }

	/**
	 * Sets and deletes values and writes the config.php
	 *
	 * @param array $configs Associative array with `key => value` pairs
	 *                       If value is null, the config key will be deleted
	 */
	public function setValues(array $configs)
 {
 }

	/**
	 * Looks up a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getValue($key, $default = '')
 {
 }

	/**
	 * Looks up a system wide defined value and filters out sensitive data
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getFilteredValue($key, $default = '')
 {
 }

	/**
	 * Delete a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 */
	public function deleteValue($key)
 {
 }

	/**
	 * @param bool|array $keysToRemove
	 * @param mixed $value
	 * @return mixed
	 */
	protected function removeSensitiveValue($keysToRemove, $value)
 {
 }
}
