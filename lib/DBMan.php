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
		
		$joins = Appconf::dbman ('Joins');
		
		switch (DBMan::driver ()) {
			case 'sqlite':
				$res = db_fetch_array ('pragma table_info(' . $table . ')');
				foreach ($res as $row) {
					$type = DBMan::parse_type ($row->type);
					$info = (object) array (
						'name' => $row->name,
						'type' => $type['type'],
						'length' => $type['length'],
						'notnull' => ($row->notnull == 1) ? 'No' : 'Yes',
						'key' => ($row->pk == 1) ? 'Primary' : '',
						'default' => trim ($row->dflt_value, '"'),
						'extra' => '',
						'original' => $row
					);
					
					if (isset ($joins[$table][$info->name])) {
						$info->type = 'select';
						$info->values = DBMan::select_values ($table, $info, $row, $joins);
					}
					$out[] = $info;
				}
				break;

			case 'mysql':
				$res = db_fetch_array ('describe `' . $table . '`');
				foreach ($res as $row) {
					$type = DBMan::parse_type ($row->Type);
					$info = (object) array (
						'name' => $row->Field,
						'type' => $type['type'],
						'length' => $type['length'],
						'notnull' => ($row->Null == 'NO') ? 'No' : 'Yes',
						'key' => ($row->Key == 'PRI') ? 'Primary' : (! empty ($row->Key)) ? 'Secondary' : '',
						'default' => $row->Default,
						'extra' => $row->Extra,
						'original' => $row
					);
					
					if ($info->type === 'enum') {
						$info->values = DBMan::enum_values ($row->Type);
					}
					
					if (isset ($joins[$table][$info->name])) {
						$info->type = 'select';
						$info->values = DBMan::select_values ($table, $info, $row, $joins);
					}
					$out[] = $info;
				}
				break;
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
				$rows = [];
				
				foreach ($res as $row) {
					if ($row->pk == 1) {
						$rows[] = $row->name;
					}
				}
				
				if (count ($rows) == 1) {
					return $rows[0];
				} elseif (count ($rows) > 1) {
					return $rows;
				}
				
				break;
				
			case 'mysql':
				$res = db_fetch_array ('describe `' . $table . '`');
				$rows = [];
				
				foreach ($res as $row) {
					if ($row->Key == 'PRI') {
						$rows[] = $row->Field;
					}
				}
				
				if (count ($rows) == 1) {
					return $rows[0];
				} elseif (count ($rows) > 1) {
					return $rows;
				}
				
				break;
		}
		return false;
	}
	
	/**
	 * Takes the primary_key() info and a database row and returns the
	 * primary key value for it. If it's a single-column primary key,
	 * the value is the column value. If it's a multi-column primary key,
	 * the values are joined by a pipe character.
	 */
	public static function pkey_value ($row, $pkey) {
		if (is_array ($pkey)) {
			$sep = '';
			$val = '';
			foreach ($pkey as $key) {
				$val .= $sep . $row->{$key};
				$sep = '|';
			}
			return $val;
		}
		return $row->{$pkey};
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
	 * Retrieve the values for an enum type.
	 */
	public static function enum_values ($type) {
		$type = substr ($type, 6, -2);
		return explode ("','", stripslashes ($type));
	}
	
	/**
	 * Retrieve the values for a select type (fields in the [Joins] config block).
	 */
	public static function select_values ($table, $info, $row, $joins) {
		$default = is_numeric ($info->default) ? (int) $info->default : $info->default;
		$values = [];
			
		if (preg_match ('/^`.*`$/', $joins[$table][$info->name])) {
			$method = substr ($joins[$table][$info->name], 1, -1);
			$values = call_user_func ($method);

		} else {
			list ($other_table, $key_field, $value_field) = explode ('.', $joins[$table][$info->name]);
			$values = DB::pairs ('select `' . $key_field . '`, `' . $value_field . '` from `' . $other_table . '` order by `' . $value_field . '` asc');
		}
		
		if (! isset ($values[$default])) {
			$values = [$default => __ ('- default value -')] + $values;
		}
		
		//info ($default, true);
		//info ($values);
		
		return $values;
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

		// ensure non-nullable fields aren't empty
		$empty_ok = array ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob', 'select');
		if ($field->notnull == 'No' && ! in_array ($field->type, $empty_ok)) {
			$rules['not empty'] = 1;
		} else {
			$rules['skip_if_empty'] = 1;
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