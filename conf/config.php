; <?php /*

; Duplicate to conf/app.dbman.config.php and modify as follows:

[Joins]

; table_name[field_name] = other_table.id_field.label_field
; table_name[field_name] = `myapp\Util::get_values_for_field_name`

[Features]

add = On
edit = On
delete = On
drop = On
shell = On
export = On
import = On
schema = On

[Admin]

handler = dbman/index
name = DB Manager

; */ ?>