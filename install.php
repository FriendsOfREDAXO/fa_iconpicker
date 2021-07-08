<?php

// install db table
rex_sql_table::get(rex::getTable('fa_icons'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(50)'))
    ->ensureColumn(new rex_sql_column('code', 'varchar(5)'))
    ->ensureColumn(new rex_sql_column('svg', 'text', true, null))
    ->ensureColumn(new rex_sql_column('label', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('searchterms', 'text', true))
    ->ensureColumn(new rex_sql_column("weight", "varchar(2)"))
    ->ensureColumn(new rex_sql_column("variant", "enum('free','pro')"))
    ->ensureColumn(new rex_sql_column('version', 'varchar(20)'))
    ->ensureColumn(new rex_sql_column("subset", "varchar(40)", true))
    ->ensureColumn(new rex_sql_column('createdate', 'DATETIME', false, 'CURRENT_TIMESTAMP'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(192)', false, ''))
    ->ensure();

// put content of packs to data folder
try {
    $sourcePath = rex_path::addon(rex_fa_package::PACKAGE . "/packages");
    $targetPath = rex_path::data(rex_fa_package::PACKAGE_PATH);

    rex_dir::copy($sourcePath, $targetPath);
} catch (Exception $e) {
    $this->setProperty('installmsg', $e->getMessage());
    $this->setProperty('install', false);
    exit();
}

// import packages if not existing
try {
    rex_fa_package::import(null, false);

    // fallback
    if(is_null(rex_fa_iconpicker::getActiveVariant())) {
        $latest = rex_fa_package::getLatestPackage();

        if(rex_fa_package::packageExists($latest)) {
            $latest->setActive();
        }
    }
} catch (Exception $e) {
    $this->setProperty('installmsg', $e->getMessage());
    $this->setProperty('install', false);
    exit();
}

// set default settings for picker widget
require_once(rex_path::addon($this->getName())."lib/rex.fa.settings.php");

foreach($faIconPickerSettings as $key => $setting) {
    $this->setConfig("widget-" . $key, $setting);
}
