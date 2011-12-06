<?php

	/**
	 * @package textboxfield
	 */

	/**
	 * An enhanced text input field.
	 */
	class Extension_TextBoxField extends Extension {
		/**
		 * The name of the field settings table.
		 */
		const FIELD_TABLE = 'tbl_fields_textbox';

		/**
		 * Publish page headers.
		 */
		const PUBLISH_HEADERS = 1;

		/**
		 * Datasource filter page headers.
		 */
		const FILTER_HEADERS = 2;

		/**
		 * Publish settings page headers.
		 */
		const SETTING_HEADERS = 4;

		/**
		 * What headers have been appended?
		 *
		 * @var integer
		 */
		static protected $appendedHeaders = 0;

		/**
		 * Add headers to the page.
		 */
		static public function appendHeaders($type) {
			if (
				(self::$appendedHeaders & $type) !== $type
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				$page = Administration::instance()->Page;

				if ($type === self::PUBLISH_HEADERS) {
					$page->addStylesheetToHead(URL . '/extensions/textboxfield/assets/publish.css', 'screen', 10251840);
					$page->addScriptToHead(URL . '/extensions/textboxfield/assets/publish.js', 10251840);
				}

				if ($type === self::FILTER_HEADERS) {
					$page->addScriptToHead(URL . '/extensions/textboxfield/assets/interface.js', 10251840);
					$page->addScriptToHead(URL . '/extensions/textboxfield/assets/filtering.js', 10251841);
					$page->addStylesheetToHead(URL . '/extensions/textboxfield/assets/filtering.css', 'screen', 10251840);
				}

				if ($type === self::SETTING_HEADERS) {
					$page->addStylesheetToHead(URL . '/extensions/textboxfield/assets/settings.css', 'screen', 10251840);
				}

				self::$appendedHeaders &= $type;
			}
		}

		/**
		 * Extension information.
		 *
		 * @return array
		 */
		public function about() {
			return array(
				'name'			=> 'Field: Text Box',
				'version'		=> '2.3',
				'release-date'	=> '2011-12-05',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://nbsp.io/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description' => 'An enhanced text input field.'
			);
		}

		/**
		 * Cleanup installation.
		 *
		 * @return boolean
		 */
		public function uninstall() {
			Symphony::Database()->query(sprintf(
				"DROP TABLE `%s`",
				self::FIELD_TABLE
			));

			return true;
		}

		/**
		 * Create tables and configuration.
		 *
		 * @return boolean
		 */
		public function install() {
			Symphony::Database()->query(sprintf("
				CREATE TABLE IF NOT EXISTS `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`column_length` INT(11) UNSIGNED DEFAULT 75,
					`text_size` ENUM('single', 'small', 'medium', 'large', 'huge') DEFAULT 'medium',
					`text_formatter` VARCHAR(255) DEFAULT NULL,
					`text_validator` VARCHAR(255) DEFAULT NULL,
					`text_length` INT(11) UNSIGNED DEFAULT 0,
					`text_cdata` ENUM('yes', 'no') DEFAULT 'no',
					`text_handle` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
				self::FIELD_TABLE
			));

			return true;
		}

		/**
		 * Update extension from previous releases.
		 *
		 * @see toolkit.ExtensionManager#update()
		 * @param string $previousVersion
		 * @return boolean
		 */
		public function update($previousVersion) {
			// Column length:
			if ($this->updateHasColumn('show_full')) {
				$this->updateRemoveColumn('show_full');
			}

			if (!$this->updateHasColumn('column_length')) {
				$this->updateAddColumn('column_length', 'INT(11) UNSIGNED DEFAULT 75 AFTER `field_id`');
			}

			// Text size:
			if ($this->updateHasColumn('size')) {
				$this->updateRenameColumn('size', 'text_size');
			}

			// Text formatter:
			if ($this->updateHasColumn('formatter')) {
				$this->updateRenameColumn('formatter', 'text_formatter');
			}

			// Text validator:
			if ($this->updateHasColumn('validator')) {
				$this->updateRenameColumn('validator', 'text_validator');
			}

			// Text length:
			if ($this->updateHasColumn('length')) {
				$this->updateRenameColumn('length', 'text_length');
			}

			else if (!$this->updateHasColumn('text_length')) {
				$this->updateAddColumn('text_length', 'INT(11) UNSIGNED DEFAULT 0 AFTER `text_formatter`');
			}

			// Text CDATA:
			if (!$this->updateHasColumn('text_cdata')) {
				$this->updateAddColumn('text_cdata', "ENUM('yes', 'no') DEFAULT 'no' AFTER `text_length`");
			}

			// Text handle:
			if (!$this->updateHasColumn('text_handle')) {
				$this->updateAddColumn('text_handle', "ENUM('yes', 'no') DEFAULT 'no' AFTER `text_cdata`");
			}

			return true;
		}

		/**
		 * Add a new column to the settings table.
		 *
		 * @param string $columm
		 * @param string $type
		 * @return boolean
		 */
		public function updateAddColumn($column, $type, $table = self::FIELD_TABLE) {
			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				ADD COLUMN
					`{$column}` {$type}
				",
				$table
			));
		}

		/**
		 * Does the settings table have a column?
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateHasColumn($column, $table = self::FIELD_TABLE) {
			return (boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
					SHOW COLUMNS FROM
						`%s`
					WHERE
						Field = '{$column}'
				",
				$table
			));
		}

		/**
		 * Remove a column from the settings table.
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateRemoveColumn($column, $table = self::FIELD_TABLE) {
			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				DROP COLUMN
					`{$column}`
				",
				$table
			));
		}

		/**
		 * Rename a column in the settings table.
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateRenameColumn($from, $to, $table = self::FIELD_TABLE) {
			$data = Symphony::Database()->fetchRow(0, sprintf("
					SHOW COLUMNS FROM
						`%s`
					WHERE
						Field = '{$from}'
				",
				$table
			));

			if (!is_null($data['Default'])) {
				$type = 'DEFAULT ' . var_export($data['Default'], true);
			}

			else if ($data['Null'] == 'YES') {
				$type .= 'DEFAULT NULL';
			}

			else {
				$type .= 'NOT NULL';
			}

			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				CHANGE
					`%s` `%s` %s
				",
				$table, $from, $to,
				$data['Type'] . ' ' . $type
			));
		}
	}