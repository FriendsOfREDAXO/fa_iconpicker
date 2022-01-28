<?php
$addon = rex_addon::get('fa_iconpicker');
// delete files
try {
   rex_dir::delete($addon->getDataPath());
} catch (Exception $e) {}
// delete db table
rex_sql_table::get(rex::getTable('fa_icons'))->drop();
