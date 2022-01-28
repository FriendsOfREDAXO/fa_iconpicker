<?php
set_time_limit (0);

// check fopr uploads
$delete_file = 0;

if(isset($_POST['delete_file'])){
    $delete_file = $_POST['delete_file'];
}

$targetPath = rex_path::data(rex_fa_package::PACKAGE_PATH)."uploads/";
$activeSubset = rex_fa_iconpicker::getActiveSubset();

// handle make active requests
if(rex_request("make-active", "int") == 1) {
    $variant = rex_request("variant", "string", "");
    $version = rex_request("version", "string", "");
    $subset = rex_request("subset", "string", "");

    if(trim($variant) != "" && trim($version) != "") {
        $package = new rex_fa_package($variant, $version, ($subset == "" ? null : $subset));

        if (rex_fa_package::packageExists($package)) {
            $package->setActive();

            $activeVersion = $package->getVersion();
            $activeVariant = $package->getVariant();
            $activeSubset = $package->getSubset();
        }
    }
}

// handle package delete requests
if(rex_request("delete", "int") == 1) {
    $variant = rex_request("variant", "string", "");
    $version = rex_request("version", "string", "");
    $subset = rex_request("subset", "string", "");

    if(trim($variant) != "" && trim($version) != "") {
        $package = new rex_fa_package($variant, $version, ($subset == "" ? null : $subset));

        if (rex_fa_package::packageExists($package)) {
            $package->delete();
        }
    }
}

// handle upload(s)
if(!empty($_FILES) && rex_post("delete", "int", 0) == 0) {
    if(file_exists($targetPath) && is_dir($targetPath)) {
        if (is_writable($targetPath) ) {
            $tempFile = $_FILES['file']['tmp_name'];
            $targetFile = $targetPath . $_FILES['file']['name'];
            
            // check if there is any file with the same name
            if(!file_exists($targetFile)) {
                move_uploaded_file($tempFile, $targetFile);
                
                // be sure that the file has been uploaded
                if (file_exists($targetFile)) {
                    // import file
                    try {
                        $errors = rex_fa_package::import($_FILES['file']['name']);

                        if(count($errors) == 0) {
                            $uploadSuccess = array(
                                'status' => 'success',
                                'name' => $_FILES['file']['name']
                            );
                        }
                    } catch (Exception $e) {
                        $uploadError = $e->getMessage();
                    }
                } else {
                    $uploadError = sprintf(rex_i18n::rawMsg("fa_iconpicker_error_file_upload_failed"), $_FILES['file']['name']);
                }
            } else {
                $uploadError = sprintf(rex_i18n::rawMsg("fa_iconpicker_error_file_already_exists"), $_FILES['file']['name']);
            }
        } else {
            $uploadError = rex_i18n::rawMsg("fa_iconpicker_error_upload_not_writable");
        }
    } else {
        $uploadError = rex_i18n::rawMsg("fa_iconpicker_error_upload_not_existing");
    }

    ob_clean();

    if(isset($uploadSuccess)) {
        rex_response::setStatus(rex_response::HTTP_OK);
        rex_response::sendContent(json_encode($uploadSuccess));
    } else {
        rex_response::setStatus("HTTP/1.0 400 Bad Request");
        rex_response::sendContent($uploadError);
    }

    die();
}
?>

<div class="row">
<?php

// get current active package
$activeVariant = (!isset($activeVariant) ? rex_fa_iconpicker::getActiveVariant() : $activeVariant);
$activeVersion = (!isset($activeVersion) ? rex_fa_iconpicker::getActiveVersion() : $activeVersion);

// pro packages
$proPackages = rex_fa_iconpicker::getPackages("pro");

ob_start();
?>

<div class="sortable-list rex-fa-packages-wrapper rex-fa-packages-wrapper-pro">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th><?= rex_i18n::msg("fa_iconpicker_version"); ?></th>
            <th><?= rex_i18n::msg("fa_iconpicker_icons"); ?></th>
            <th><?= rex_i18n::msg("fa_iconpicker_weights"); ?></th>
            <th><?= rex_i18n::msg("yform_actions"); ?></th>
        </tr>
        </thead>
        <tbody class="ui-sortable">
        <?php
        if(count($proPackages) == 0) {
            echo '<tr><td colspan="4"><br /><center><i>'.rex_i18n::msg("fa_iconpicker_info_nopackages_pro").'</i></center></td></tr>';
        } else {
            foreach($proPackages as $pack) {
                $version = explode(".", $pack->getVersion());
                $version[0] = '<span class="version v-'.$version[0].'">'.$version[0].'</span>';
                $version = implode(".", $version);

                echo '<tr '.($activeVersion == $pack->getVersion() && $activeVariant == $pack->getVariant() && $activeSubset === $pack->getSubset() ? 'class="active"' : '').'>
                        <td class="title">'.$version.'</td>
                        <td class="icons">'.
                            count($pack->getIcons()).
                            (!is_null($pack->getSubset()) ? '<span class="subset" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_packages_subset").'">S</span>' : '').
                        '</td>
                        <td class="weights">'.implode(", ", $pack->getWeights()).'</td>
                        <td class="actions">';

                if($activeVersion != $pack->getVersion() || $activeVariant != $pack->getVariant()  || $activeSubset !== $pack->getSubset()) {
                    echo '<form action="/redaxo/index.php?page='.rex_be_controller::getCurrentPage().'" method="post">
                              <button class="btn btn-primary" name="make-active" value="1" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_action_makedefault").'"><i class="rex-icon fa-check"></i></button>
                              <button class="btn btn-delete cancel" name="delete" value="1" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_action_delete").'"><i class="rex-icon fa-trash"></i></button>
                              <input type="hidden" name="variant" value="'.$pack->getVariant().'" />
                              <input type="hidden" name="version" value="'.$pack->getVersion().'" />
                              <input type="hidden" name="subset" value="'.$pack->getSubset().'" />
                          </form>';
                } else {
                    echo '<i>'.rex_i18n::msg("fa_iconpicker_active_package").'</i>';
                }

                echo '</td></tr>';
            }
        }
        ?>
        </tbody>
    </table
</div>

<?php
$proPackagesList = ob_get_contents();
ob_end_clean();

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('fa_iconpicker_packages_pro'), false);
$fragment->setVar('body', $proPackagesList, false);
echo '<div class="col-md-4">'.$fragment->parse('core/page/section.php').'</div>';

// free packages
$freePackages = rex_fa_iconpicker::getPackages("free");

ob_start();
?>

<div class="sortable-list rex-fa-packages-wrapper rex-fa-packages-wrapper-free">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= rex_i18n::msg("fa_iconpicker_version"); ?></th>
                <th><?= rex_i18n::msg("fa_iconpicker_icons"); ?></th>
                <th><?= rex_i18n::msg("fa_iconpicker_weights"); ?></th>
                <th><?= rex_i18n::msg("yform_actions"); ?></th>
            </tr>
        </thead>
        <tbody class="ui-sortable">
            <?php
            if(count($freePackages) == 0) {
                echo '<tr><td colspan="4"><br /><center><i>'.rex_i18n::msg("fa_iconpicker_info_nopackages_free").'</i></center></td></tr>';
            } else {
                foreach($freePackages as $pack) {
                    $version = explode(".", $pack->getVersion());
                    $version[0] = '<span class="version v-'.$version[0].'">'.$version[0].'</span>';
                    $version = implode(".", $version);

                    echo '<tr '.($activeVersion == $pack->getVersion() && $activeVariant == $pack->getVariant() && $activeSubset === $pack->getSubset() ? 'class="active"' : '').'>
                            <td class="title">'.$version.'</td>
                            <td class="icons">'.
                                count($pack->getIcons()).
                                (!is_null($pack->getSubset()) ? '<span class="subset" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_packages_subset").'">S</span>' : '').
                            '</td>
                            <td class="weights">'.implode(", ", $pack->getWeights()).'</td>
                            <td class="actions">';

                    if($activeVersion != $pack->getVersion() || $activeVariant != $pack->getVariant() || $activeSubset !== $pack->getSubset()) {
                        echo '<form action="/redaxo/index.php?page='.rex_be_controller::getCurrentPage().'" method="post">
                                  <button class="btn btn-primary" name="make-active" value="1" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_action_makedefault").'"><i class="rex-icon fa-check"></i></button>
                                  '.(count($freePackages) > 1 ?
                                    '<button class="btn btn-delete cancel" name="delete" value="1" data-toggle="tooltip" data-placement="top" title="'.rex_i18n::msg("fa_iconpicker_action_delete").'"><i class="rex-icon fa-trash"></i></button>' :
                                    '').'
                                  <input type="hidden" name="variant" value="'.$pack->getVariant().'" />
                                  <input type="hidden" name="version" value="'.$pack->getVersion().'" />
                                  <input type="hidden" name="subset" value="'.$pack->getSubset().'" />
                              </form>';
                    } elseif($activeVersion == $pack->getVersion() && $activeVariant == $pack->getVariant() && $activeSubset === $pack->getSubset()) {
                        echo '<i>'.rex_i18n::msg("fa_iconpicker_active_package").'</i>';
                    }
//                    else {
//                        echo '<i>'.rex_i18n::msg("fa_iconpicker_last_free_package").'</i>';
//                    }

                    echo '</td></tr>';
                }
            }
            ?>
        </tbody>
    </table>
</div>

<?php
$freePackagesList = ob_get_contents();
ob_end_clean();

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('fa_iconpicker_packages_free'), false);
$fragment->setVar('body', $freePackagesList, false);
echo '<div class="col-md-4">'.$fragment->parse('core/page/section.php').'</div>';

// upload packages
$upload = '
<form action="/redaxo/index.php?page='.rex_be_controller::getCurrentPage().'" class="dropzone files-container" id="fa-picker-upload">
	<div class="fallback">
		<input name="rex-fa-file" type="file" multiple />
		<br />
		<button class="btn btn-save" name="rex-fa-upload">'.rex_i18n::msg("fa_iconpicker_upload").'</button>
		<input type="hidden" name="page" value="'.rex_be_controller::getCurrentPage().'">
	</div>
</form>
<div class="rex-form-panel-footer dropzone-actions hidden">
    <div class="btn-toolbar">
        <button class="btn btn-save" type="button" id="rex-fa5-start-upload">'.rex_i18n::msg("fa_iconpicker_action_startupload").'</button>
        <button class="btn btn-warning cancel" type="button" id="rex-fa5-clear-queue">'.rex_i18n::msg("fa_iconpicker_action_clearqueue").'</button>
    </div>
</div>
';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('fa_iconpicker_packages_upload'), false);
$fragment->setVar('body', $upload, false);
echo '<div class="col-md-4">'.$fragment->parse('core/page/section.php').'</div>';
?>
</div>
