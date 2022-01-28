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

// check if mm type already installed, else add
$mmEffect = rex_sql::factory()->getArray("SELECT id FROM ".rex::getTable('media_manager_type')." WHERE name = :name", [':name' => rex_i18n::msg('fa_iconpicker_mm_name')]);

if(count($mmEffect) == 0) {
    // add effect for showing css
    $mmEffect = rex_sql::factory();
    $mmEffect->setTable(rex::getTable("media_manager_type"));
    $mmEffect->setValues([
        "name" => rex_i18n::msg('fa_iconpicker_mm_name'),
        "description" => rex_i18n::rawMsg("fa_iconpicker_mm_description"),
    ]);
    $mmEffect->addGlobalCreateFields();
    $mmEffect->addGlobalUpdateFields();
    $mmEffect->insert();

    // add effect configs
    $mmEffectConfig = rex_sql::factory();
    $mmEffectConfig->setTable(rex::getTable("media_manager_type_effect"));
    $mmEffectConfig->setValues([
        "type_id" => $mmEffect->getLastId(),
        "effect" => "font_awesome_path",
        "parameters" => '{"rex_effect_rounded_corners":{"rex_effect_rounded_corners_topleft":"","rex_effect_rounded_corners_topright":"","rex_effect_rounded_corners_bottomleft":"","rex_effect_rounded_corners_bottomright":""},"rex_effect_workspace":{"rex_effect_workspace_width":"","rex_effect_workspace_height":"","rex_effect_workspace_hpos":"left","rex_effect_workspace_vpos":"top","rex_effect_workspace_set_transparent":"colored","rex_effect_workspace_bg_r":"","rex_effect_workspace_bg_g":"","rex_effect_workspace_bg_b":""},"rex_effect_crop":{"rex_effect_crop_width":"","rex_effect_crop_height":"","rex_effect_crop_offset_width":"","rex_effect_crop_offset_height":"","rex_effect_crop_hpos":"center","rex_effect_crop_vpos":"middle"},"rex_effect_insert_image":{"rex_effect_insert_image_brandimage":"","rex_effect_insert_image_hpos":"left","rex_effect_insert_image_vpos":"top","rex_effect_insert_image_padding_x":"-10","rex_effect_insert_image_padding_y":"-10"},"rex_effect_rotate":{"rex_effect_rotate_rotate":"0"},"rex_effect_filter_colorize":{"rex_effect_filter_colorize_filter_r":"","rex_effect_filter_colorize_filter_g":"","rex_effect_filter_colorize_filter_b":""},"rex_effect_image_properties":{"rex_effect_image_properties_jpg_quality":"","rex_effect_image_properties_png_compression":"","rex_effect_image_properties_webp_quality":"","rex_effect_image_properties_interlace":null},"rex_effect_filter_brightness":{"rex_effect_filter_brightness_brightness":""},"rex_effect_flip":{"rex_effect_flip_flip":"X"},"rex_effect_image_format":{"rex_effect_image_format_convert_to":"webp"},"rex_effect_filter_contrast":{"rex_effect_filter_contrast_contrast":""},"rex_effect_filter_sharpen":{"rex_effect_filter_sharpen_amount":"80","rex_effect_filter_sharpen_radius":"0.5","rex_effect_filter_sharpen_threshold":"3"},"rex_effect_resize":{"rex_effect_resize_width":"","rex_effect_resize_height":"","rex_effect_resize_style":"maximum","rex_effect_resize_allow_enlarge":"enlarge"},"rex_effect_filter_blur":{"rex_effect_filter_blur_repeats":"10","rex_effect_filter_blur_type":"gaussian","rex_effect_filter_blur_smoothit":""},"rex_effect_mirror":{"rex_effect_mirror_height":"","rex_effect_mirror_opacity":"100","rex_effect_mirror_set_transparent":"colored","rex_effect_mirror_bg_r":"","rex_effect_mirror_bg_g":"","rex_effect_mirror_bg_b":""},"rex_effect_header":{"rex_effect_header_download":"open_media","rex_effect_header_cache":"no_cache","rex_effect_header_filename":"filename"},"rex_effect_convert2img":{"rex_effect_convert2img_convert_to":"jpg","rex_effect_convert2img_density":"150","rex_effect_convert2img_color":""},"rex_effect_mediapath":{"rex_effect_mediapath_mediapath":""}}',
    ]);
    $mmEffectConfig->addGlobalCreateFields();
    $mmEffectConfig->addGlobalUpdateFields();
    $mmEffectConfig->insert();

    // add effect for font src pathes in css
    $mmEffect = rex_sql::factory();
    $mmEffect->setTable(rex::getTable("media_manager_type"));
    $mmEffect->setValues([
        "name" => rex_i18n::msg('fa_iconpicker_mm_fontsrc_name'),
        "description" => rex_i18n::rawMsg("fa_iconpicker_mm_fontsrc_description"),
    ]);
    $mmEffect->addGlobalCreateFields();
    $mmEffect->addGlobalUpdateFields();
    $mmEffect->insert();

    // add effect configs
    $mmEffectConfig = rex_sql::factory();
    $mmEffectConfig->setTable(rex::getTable("media_manager_type_effect"));
    $mmEffectConfig->setValues([
        "type_id" => $mmEffect->getLastId(),
        "effect" => "font_awesome_fontsrc_path",
        "parameters" => '{"rex_effect_rounded_corners":{"rex_effect_rounded_corners_topleft":"","rex_effect_rounded_corners_topright":"","rex_effect_rounded_corners_bottomleft":"","rex_effect_rounded_corners_bottomright":""},"rex_effect_workspace":{"rex_effect_workspace_width":"","rex_effect_workspace_height":"","rex_effect_workspace_hpos":"left","rex_effect_workspace_vpos":"top","rex_effect_workspace_set_transparent":"colored","rex_effect_workspace_bg_r":"","rex_effect_workspace_bg_g":"","rex_effect_workspace_bg_b":""},"rex_effect_crop":{"rex_effect_crop_width":"","rex_effect_crop_height":"","rex_effect_crop_offset_width":"","rex_effect_crop_offset_height":"","rex_effect_crop_hpos":"center","rex_effect_crop_vpos":"middle"},"rex_effect_insert_image":{"rex_effect_insert_image_brandimage":"","rex_effect_insert_image_hpos":"left","rex_effect_insert_image_vpos":"top","rex_effect_insert_image_padding_x":"-10","rex_effect_insert_image_padding_y":"-10"},"rex_effect_rotate":{"rex_effect_rotate_rotate":"0"},"rex_effect_filter_colorize":{"rex_effect_filter_colorize_filter_r":"","rex_effect_filter_colorize_filter_g":"","rex_effect_filter_colorize_filter_b":""},"rex_effect_image_properties":{"rex_effect_image_properties_jpg_quality":"","rex_effect_image_properties_png_compression":"","rex_effect_image_properties_webp_quality":"","rex_effect_image_properties_interlace":null},"rex_effect_filter_brightness":{"rex_effect_filter_brightness_brightness":""},"rex_effect_flip":{"rex_effect_flip_flip":"X"},"rex_effect_image_format":{"rex_effect_image_format_convert_to":"webp"},"rex_effect_filter_contrast":{"rex_effect_filter_contrast_contrast":""},"rex_effect_filter_sharpen":{"rex_effect_filter_sharpen_amount":"80","rex_effect_filter_sharpen_radius":"0.5","rex_effect_filter_sharpen_threshold":"3"},"rex_effect_resize":{"rex_effect_resize_width":"","rex_effect_resize_height":"","rex_effect_resize_style":"maximum","rex_effect_resize_allow_enlarge":"enlarge"},"rex_effect_filter_blur":{"rex_effect_filter_blur_repeats":"10","rex_effect_filter_blur_type":"gaussian","rex_effect_filter_blur_smoothit":""},"rex_effect_mirror":{"rex_effect_mirror_height":"","rex_effect_mirror_opacity":"100","rex_effect_mirror_set_transparent":"colored","rex_effect_mirror_bg_r":"","rex_effect_mirror_bg_g":"","rex_effect_mirror_bg_b":""},"rex_effect_header":{"rex_effect_header_download":"open_media","rex_effect_header_cache":"no_cache","rex_effect_header_filename":"filename"},"rex_effect_convert2img":{"rex_effect_convert2img_convert_to":"jpg","rex_effect_convert2img_density":"150","rex_effect_convert2img_color":""},"rex_effect_mediapath":{"rex_effect_mediapath_mediapath":""}}',
    ]);
    $mmEffectConfig->addGlobalCreateFields();
    $mmEffectConfig->addGlobalUpdateFields();
    $mmEffectConfig->insert();
}

// check all installed packages for correct font-src pathes
//$activeCss = rex_fa_iconpicker::getCssPath();
$packages = rex_fa_iconpicker::getPackages();

foreach($packages as $p) {
    $cssFile = rex_fa_iconpicker::getCssPathSpecific(
        $p->getVariant(),
        $p->getVersion(),
        $p->getSubset()
    );

    if(file_exists($cssFile)) {
        $content = rex_file::get($cssFile);

        $newContent = preg_replace(
            "@url\(\.\.\/webfonts\/([a-z0-9\-\.]+)([^\)]+)?\)@i",
            "url(".rtrim(rex::getServer(), "/")."/index.php?rex_media_type=".rex_i18n::msg('fa_iconpicker_mm_fontsrc_name')."&rex_media_file=$1$2)",
            $content,
            99
        );

        // fixing the fix
//        $newContent = preg_replace(
//            "@url\(\.\/index.php\?rex_media_type=font-awesome-font-src&rex_media_file=([a-z0-9\-\.]+)([^\)]+)?\)@i",
//            "url(".rtrim(rex::getServer(), "/")."/index.php?rex_media_type=".rex_i18n::msg('fa_iconpicker_mm_fontsrc_name')."&rex_media_file=$1$2)",
//            $newContent,
//            99
//        );

        rex_file::put($cssFile, $newContent);
    }
}