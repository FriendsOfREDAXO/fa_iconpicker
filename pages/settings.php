<?php
// echo 'Aktuell eingestelltes Paket: '.rex_fa_iconpicker::getActiveVersion()." | ".rex_fa_iconpicker::getActiveVariant();
$userLang = rex::getUser()->getLanguage();
$addon = rex_addon::get(rex_fa_package::PACKAGE);
$form = rex_config_form::factory(rex_fa_package::PACKAGE);

$currentConfig = $addon->getConfig();

require(rex_path::addon($this->getName())."lib/rex.fa.settings.php");

$counter = 0;
$form->addRawField('<div class="row"><div class="col-lg-6">');

foreach($faIconPickerSettings as $key => $setting) {
    // break on classes
    if($key == 'class') {
        $form->addRawField('</div><div class="col-lg-6">');
    }

    switch(gettype($setting)) {
        // special cases
        case 'NULL':
            switch($key) {
                case 'icons':  $field = $form->addTextAreaField("widget-".$key, null, ['class' => 'form-control']); break;
                default:
                    if(preg_match("@^on@", $key) && is_null($setting)) {
                        $field = $form->addTextAreaField("widget-".$key, null, ['style' => 'height: 100px', 'class' => 'form-control']); break; // rex-code rex-js-code
                    }
                    break;
            }
            break;

        case 'boolean':
            $attr = [];

            if($currentConfig["widget-".$key]) {
                $attr = ['checked' => true];
            }

            $field = $form->addInputField("checkbox", "widget-".$key, 1, $attr);
            break;

        case 'integer':
            $field = $form->addInputField("number", "widget-".$key, null, ['class' => 'form-control', 'type' => 'number', 'style' => 'width: 100px;', 'min' => 0]);
            break;

        case 'string':
            switch($key) {
                case 'insert-value':
                    $field = $form->addSelectField("widget-".$key);
                    $select = $field->getSelect();

                    foreach (['name' => 'Name', 'code' => 'Code', 'svg' => 'SVG-Code', 'label' => 'Label'] as $val => $label) {
                        $select->addOption($label, $val);
                    }
                    break;

                case 'preview-weight':
                    $field = $form->addSelectField("widget-".$key);
                    $select = $field->getSelect();

                    foreach (['T' => 'Thin', 'L' => 'Light', 'R' => 'Regular', 'S' => 'Solid', 'D' => 'Duotone', 'B' => 'Brand'] as $val => $label) {
                        $select->addOption($label, $val);
                    }
                    break;

                case 'sort-by':
                    $field = $form->addSelectField("widget-".$key);
                    $select = $field->getSelect();

                    foreach (['id' => 'ID', 'name' => 'Name', 'label' => 'Label', 'code' => 'Code', 'createdate' => 'angelegt am'] as $val => $label) {
                        $select->addOption(($userLang == '' || $userLang == 'de_de' ? $label : $val), $val);
                    }
                    break;

                case 'sort-direction':
                    $field = $form->addSelectField("widget-".$key);
                    $select = $field->getSelect();

                    foreach (['asc' => 'aufsteigend', 'desc' => 'absteigend'] as $val => $label) {
                        $select->addOption(($userLang == '' || $userLang == 'de_de' ? $label : $val), $val);
                    }
                    break;

                default:
                    $field = $form->addInputField("text", "widget-" . $key, null, ['class' => 'form-control']);
                    break;
            }
            break;
    }

    // set labels
    $field->setLabel($addon->i18n('fa_iconpicker_config_'.$key));
    $counter++;
}

// close cols and rows
$form->addRawField('</div></div>');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('fa_iconpicker_widget_settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');