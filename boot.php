<?php

// add perm for picker usage
rex_perm::register("fa_iconpicker[picker]", "Icon-Pickers: Nutzung des Widgets", rex_perm::GENERAL);
rex_perm::register("fa_iconpicker[settings]", "Icon-Picker: Einstellungen & Management der Pakete", rex_perm::GENERAL);

if (rex_addon::get('media_manager')->isAvailable()) {
    rex_media_manager::addEffect(rex_effect_font_awesome_path::class);
    rex_media_manager::addEffect(rex_effect_font_awesome_fontsrc_path::class);
}

// include current active set
if (rex::isBackend()) {
    // push translation data for init function to use it
    rex_extension::register('PAGE_HEADER', function($ep){
        $file = rex_path::addon(rex_fa_package::PACKAGE)."lang".DIRECTORY_SEPARATOR.rex_i18n::getLocale().'.lang';
        $faPickerTranslations = [];

        if (($content = rex_file::get($file)) && preg_match_all('/^([^=\s]+)\h*=\h*(\S.*)(?<=\S)/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $faPickerTranslations[$match[1]] = $match[2];
            }
        } else {
            return $ep->getSubject();
        }

        $subject = $ep->getSubject();

        // ensure all config params are present
        require_once rex_path::addon(rex_fa_package::PACKAGE)."/lib/rex.fa.settings.php";
        $currentConfig = rex_addon::get(rex_fa_package::PACKAGE)->getConfig();
        $config = [];

        foreach($faIconPickerSettings as $key => $installValue) {
            $config[$key] = (
                isset($currentConfig["widget-".$key]) ?
                (gettype($installValue) == "boolean" ?
                    (boolean)$currentConfig["widget-".$key] :
                    (gettype($installValue) == "integer" ? (int)$currentConfig["widget-".$key] : $currentConfig["widget-".$key])
                ) :
                $installValue
            );
        }

        $subject .= '
        <!-- fa picker addon -->
        <script type="text/javascript">
            let FAPickerAddonI18N = '.json_encode($faPickerTranslations).';
            
            let FAPickerSettings = '.json_encode($config).';
            
            let FAPickerPackage = {
                variant: "'. rex_fa_iconpicker::getActiveVariant() .'",
                version: "'. rex_fa_iconpicker::getActiveVersion() .'",
                subset: '.(rex_fa_iconpicker::getActiveSubset() === null ? 'null' : '"'. rex_fa_iconpicker::getActiveSubset() .'"').'
            };
        </script>
        <script src="'.rex_url::addonAssets(rex_fa_package::PACKAGE, "js/fa-iconpicker.js").'?buster='. filemtime(rex_path::addonAssets(rex_fa_package::PACKAGE, "js/fa-iconpicker.js")). '"></script>
        <!-- end fa picker addon -->
        ';

        return $subject;
    }, rex_extension::LATE, ['addon' => $this]);

    rex_view::addJsFile($this->getAssetsUrl('js/vendor/dropzone-5.7.0/dist/min/dropzone.min.js'));
    rex_view::addCssFile($this->getAssetsUrl('js/vendor/dropzone-5.7.0/dist/min/basic.min.css'));
    rex_view::addCssFile($this->getAssetsUrl('js/vendor/dropzone-5.7.0/dist/min/dropzone.min.css'));
    rex_view::addCssFile($this->getAssetsUrl('css/rex-fa-icons-backend.css'));

    // add current package if existing
    if(!is_null(rex_fa_iconpicker::getCssPath())) {
        rex_view::addCssFile('./index.php?rex_media_type='.rex_i18n::msg('fa_iconpicker_mm_name').'&rex_media_file='.rex_fa_iconpicker::getActiveCssFileName());
    }
}