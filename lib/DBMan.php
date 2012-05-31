<?php

class DBMan {
	/**
	 * Get the database driver.
	 */
	public static function driver () {
		$m = conf ('Database', 'master');
		return $m['driver'];
	}

	/**
	 * List all tables.
	 */
	public static function list_tables () {
		switch (DBMan::driver ()) {
			case 'sqlite':
				return db_shift_array ('select name from sqlite_master where type = "table" order by name asc');
			case 'mysql':
				return db_shift_array ('show tables');
		}
		return array ();
	}

	/**
	 * Return an array of columns and their details for a table.
	 */
	public static function table_info ($table) {
		$out = array ();
		switch (DBMan::driver ()) {
			case 'sqlite':
				$res = db_fetch_array ('pragma table_info(' . $table . ')');
				foreach ($res as $row) {
					$type = DBMan::parse_type ($row->type);
					$out[] = (object) array (
						'name' => $row->name,
						'type' => $type['type'],
						'length' => $type['length'],
						'notnull' => ($row->notnull == 1) ? 'No' : 'Yes',
						'key' => ($row->pk == 1) ? 'Primary' : '',
						'default' => trim ($row->dflt_value, '"'),
						'extra' => '',
						'original' => $row
					);
				}
				break;
			case 'mysql':
				$res = db_fetch_array ('describe `' . $table . '`');
				foreach ($res as $row) {
					$type = DBMan::parse_type ($row->Type);
					$out[] = (object) array (
						'name' => $row->Field,
						'type' => $type['type'],
						'length' => $type['length'],
						'notnull' => ($row->Null == 'NO') ? 'No' : 'Yes',
						'key' => ($row->Key == 'PRI') ? 'Primary' : (! empty ($row->Key)) ? 'Secondary' : '',
						'default' => $row->Default,
						'extra' => $row->Extra,
						'original' => $row
					);
				}
		}
		return $out;
	}

	/**
	 * Return the primary key field of a table. Note that this currently
	 * only supports tables with single-field primary keys.
	 */
	public static function primary_key ($table) {
		switch (DBMan::driver ()) {
			case 'sqlite':
				$res = db_fetch_array ('pragma table_info(' . $table . ')');
				foreach ($res as $row) {
					if ($row->pk == 1) {
						return $row->name;
					}
				}
				break;
			case 'mysql':
				$res = db_fetch_array ('describe `' . $table . '`');
				foreach ($res as $row) {
					if ($row->Key == 'PRI') {
						return $row->Field;
					}
				}
		}
		return false;
	}

	/**
	 * Number of rows in a table.
	 */
	public static function count ($table) {
		return db_shift ('select count(*) from ' . $table);
	}

	/**
	 * Parse a type string from a database column and return an
	 * array with type, length, and other. For example:
	 *
	 *     text -> type:'text', length:'', other:''
	 *
	 *     int(11) -> type:'int', length:'11', other:''
	 *
	 *     enum("yes","no") -> type:'enum', length:'', other:'"yes","no"'
	 */
	public static function parse_type ($type) {
		if (strpos ($type, '(') !== false) {
			list ($type, $length) = explode ('(', $type);
			$length = trim ($length, ')');
			if (is_numeric ($length)) {
				return array ('type' => $type, 'length' => $length, 'other' => '');
			} else {
				return array ('type' => $type, 'length' => '', 'other' => $length);
			}
		}
		return array ('type' => $type, 'length' => '', 'other' => '');
	}

	/**
	 * Get form rules for a field.
	 */
	public static function get_rules ($field) {
		$rules = array ();

		// skip auto-incrementing fields
		if (DBMan::is_auto_incrementing ($field)) {
			return $rules;
		}

		if ($field->notnull == 'No') {
			$rules['not empty'] = 1;
		}
		if (in_array ($field->type, array ('int', 'integer', 'float'))) {
			$rules['type'] = 'numeric';
		}
		if ($field->length != '') {
			$rules['length'] = $field->length . '-';
		}
		return $rules;
	}

	/**
	 * Determine whether the specified field is auto-incrementing.
	 */
	public static function is_auto_incrementing ($field) {
		// skip auto-incrementing fields
		if (DBMan::driver () == 'sqlite' && $field->type == 'integer' && $field->key == 'Primary') {
			return true;
		}
		if (strtolower ($field->extra) == 'auto_increment') {
			return true;
		}
		return false;
	}
}

?>