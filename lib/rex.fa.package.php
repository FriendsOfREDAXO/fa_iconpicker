<?php
/**
 * @date     02.06.2020 13:40
 * @author   Peter Schulze [p.schulze@bitshifters.de]
 */

class rex_fa_package
{
    /**
     * constants
     */
    public const PACKAGE = 'fa_iconpicker';
    public const PACKAGE_PATH = 'addons'.DIRECTORY_SEPARATOR.self::PACKAGE.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR;

    public const WEIGHT_T = 'Thin';
    public const WEIGHT_L = 'Light';
    public const WEIGHT_R = 'Regular';
    public const WEIGHT_S = 'Solid';
    public const WEIGHT_D = 'Duotone';
    public const WEIGHT_B = 'Brand';
    public const WEIGHT_THIN = 'T';
    public const WEIGHT_LIGHT = 'L';
    public const WEIGHT_REGULAR = 'R';
    public const WEIGHT_SOLID = 'S';
    public const WEIGHT_DUOTONE = 'D';
    public const WEIGHT_BRAND = 'B';

    /**
     * @var bool
     */
    private $installed = false;
    
    /**
     * @var string
     */
    private $variant;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $subset;

    /**
     * @var array
     */
    private $weights = [];

    /**
     * @var array
     */
    private $icons = [];

    /**
     * rex_fa_package constructor
     *
     * @param string|null $variant
     * @param string|null $version
     * @param string|null $subset
     * @throws Exception
     */
    public function __construct(?string $variant, ?string $version, ?string $subset = null) {
        if(is_null($variant)) {
            $variant = rex_fa_iconpicker::getActiveVariant();
        }
        if(is_null($version)) {
            $version = rex_fa_iconpicker::getActiveVersion();
        }

        $this->variant = $variant;
        $this->version = $version;
        $this->subset = $subset;

        $this->init();
    }

    /**
     * init package
     *
     * @throws rex_sql_exception
     * @author Peter Schulze [p.schulze@bitshifters.de]+
     */
    public function init() {
        if(file_exists(rex_fa_iconpicker::getCssPathSpecific($this->variant, $this->version, $this->subset))) {
            // retrieve icons
            $params = [
                ":variant" => $this->variant,
                ":version" => $this->version
            ];

            if(!is_null($this->subset)) {
                $params[':subset'] = $this->subset;
            }

            $icons = rex_sql::factory()->getArray("
                SELECT
                    *
                FROM
                    ".rex::getTable('fa_icons')."
                WHERE
                    variant = :variant AND
                    version = :version AND
                    subset ".(is_null($this->subset) ? 'IS NULL' : ' = :subset')."
                ORDER BY
                    `name` ASC,
                    FIELD(weight, 'T', 'L', 'R', 'S', 'D', 'B')
            ", $params);

            if(is_array($icons) && count($icons) > 0) {
                $this->installed = true;

                foreach($icons as $icon) {
                    // check weights
                    if(!in_array(constant('rex_fa_package::WEIGHT_'.$icon['weight']), $this->weights)) {
                        $this->weights[] = constant('rex_fa_package::WEIGHT_'.$icon['weight']);
                    }

                    // init new icon
                    if(!isset($this->icons[$icon['name']])) {
                        $this->icons[$icon['name']] = new stdClass();
                        $this->icons[$icon['name']]->weights = [$icon['weight']];

                        foreach ($icon as $field => $val) {
                            if (in_array($field, ['version', 'variant', 'weight'])) {
                                continue;
                            }

                            $this->icons[$icon['name']]->{$field} = ($field == 'id' ? intval($val) : $val);
                        }
                    }
                    // add icon weight
                    else {
                        $this->icons[$icon['name']]->weights[] = $icon['weight'];
                    }
                }
            }
        }
    }

    /**
     * import file (if not set, check whole upload folder)
     *
     * @param string|null $file
     * @param boolean $stopOnErrors
     * @return array|void
     * @throws Exception
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function import($file = null, $stopOnErrors = true) {
        $packagesFolder = rex_path::data(self::PACKAGE_PATH);
        $uploadFolder = $packagesFolder."uploads";

        if(!is_dir($uploadFolder)) {
            throw new Exception(sprintf(rex_i18n::msg("fa_iconpicker_error_upload_not_existing", $uploadFolder)), "no_upload_dir");
        } elseif(!is_writable($uploadFolder)) {
            throw new Exception(rex_i18n::msg("fa_iconpicker_error_upload_not_writable"), "upload_dir_locked");
        }

        $uploads = scandir($uploadFolder);
        $errors = [];

        // check settings to reduce amount of files later
        $packageFile = rex_path::addon(self::PACKAGE).rex_package::FILE_PACKAGE;

        if (!file_exists($packageFile)) {
            throw new Exception(rex_i18n::msg('package_missing_yml_file'));
        }

        try {
            $config = rex_file::getConfig($packageFile);
        } catch (rex_yaml_parse_exception $e) {
            echo rex_view::error(rex_i18n::msg('package_invalid_yml_file').' '.$e->getMessage());
            return;
        }

        // iterate through zip files (uploads)
        foreach($uploads as $upload) {
            if(preg_match("@^\.@", $upload) || !preg_match("@\.zip$@", $upload) || ($file != null && $upload != $file)) {
                continue;
            }

            $fileBaseName = preg_replace("/\.zip$/", "", $upload);
            $folderBaseName = null;

            // first: extract base name segments and check if package is already installed, if so > skip
            preg_match("@^(?P<prefix>fontawesome\-)?(?P<variant>[a-z]+)\-(?P<version>[0-9\.]+)\-?(?P<subset>[a-z0-9]{40})?@m", $fileBaseName, $fileNameData);

            if(isset($fileNameData['variant']) && $fileNameData['variant'] != "" && isset($fileNameData['version']) && $fileNameData['version'] != "") {
                $targetName = $fileNameData['variant']."-".$fileNameData['version'].(isset($fileNameData['subset']) ? "-".$fileNameData['subset'] : "");

                // zip already converted
                if($targetName == $fileBaseName) {
                    // check if target folder already exists
                    if(is_dir($packagesFolder.$fileNameData['variant'].DIRECTORY_SEPARATOR.$fileBaseName)) {
                        $package = new rex_fa_package(
                            $fileNameData['variant'],
                            $fileNameData['version'],
                            (isset($fileNameData['subset']) ? $fileNameData['subset'] : null)
                        );

                        if(rex_fa_package::packageExists($package)) {
                            continue;
                        }
                    }
                }
            }

            // we do NOT take over variant and version from file name (unsecure) instead determine data from zip package
            $detectedVariant = $detectedVersion = "";
            $subset = null;

            // unzip
            $zip = new ZipArchive;

            if ($zip->open($uploadFolder.DIRECTORY_SEPARATOR.$upload) === true) {
                // determine folder name
                for($i = 0; $i < $zip->numFiles; $i++){
                    $stat = $zip->statIndex($i);
                    $folderBaseName = dirname($stat['name']);
                    break;
                }

                $zip->extractTo($uploadFolder);

                // determine version in css file
                $css = $uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR."all.css";

                if(
                    is_null($folderBaseName) ||
                    !is_dir($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."metadata") ||
                    !file_exists($css)
                ) {
                    $error = sprintf(rex_i18n::rawMsg("fa_iconpicker_error_import_unknown_structure"), $upload);

                    // delete desktop stuff
                    rex_file::delete($uploadFolder.DIRECTORY_SEPARATOR.$upload);
                    rex_dir::delete($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName);

                    if($stopOnErrors) {
                        throw new Exception($error);
                    } else {
                        $errors[] = $error;
                        continue;
                    }
                }
                // check if is desktop version
                elseif(
                    !is_dir($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."webfonts") &&
                    !is_dir($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."css")
                ) {
                    $error = sprintf(rex_i18n::rawMsg("fa_iconpicker_error_import_desktop"), $upload);

                    // delete desktop stuff
                    rex_file::delete($uploadFolder.DIRECTORY_SEPARATOR.$upload);
                    rex_dir::delete($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName);

                    if($stopOnErrors) {
                        throw new Exception($error);
                    } else {
                        $errors[] = $error;
                        continue;
                    }
                }

                $cssFile = fopen($css, "r");
                $lineOne = fgets($cssFile);
                $lineTwo = fgets($cssFile);
                unset($lineOne);

                // determine version and package
                preg_match("@^\s*\* Font Awesome (?P<variant>[A-Za-z]+) (?P<version>[0-9\.]+)@m", $lineTwo, $packageData);

                if(isset($packageData['variant']) && $packageData['variant'] != "" && isset($packageData['version']) && $packageData['version'] != "") {
                    $detectedVariant = strtolower($packageData['variant']);
                    $detectedVersion = $packageData['version'];
                } else {
                    $error = sprintf(rex_i18n::msg("fa_iconpicker_error_import_extractdata", $upload));

                    if($stopOnErrors) {
                        throw new Exception($error);
                    } else {
                        $errors[] = $error;
                        continue;
                    }
                }

                // determine subset
                if(file_exists($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."README.md") &&
                   is_dir($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."otfs")
                ) {
                    $subset = sha1(rex_file::get($uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName.DIRECTORY_SEPARATOR."metadata".DIRECTORY_SEPARATOR."icons.yml"));
                }

                $zip->close();
            } else {
                throw new Exception(sprintf(rex_i18n::msg("fa_iconpicker_error_import_unzip", $upload)));
            }

            $targetName = $detectedVariant."-".$detectedVersion.(!is_null($subset) ? "-".$subset : "");
            $targetFolder = $packagesFolder.$detectedVariant.DIRECTORY_SEPARATOR.$targetName;

            // rename zip
            rex_file::move(
                $uploadFolder.DIRECTORY_SEPARATOR.$upload,
                $uploadFolder.DIRECTORY_SEPARATOR.$targetName.".zip"
            );

            // rename folder and move (TODO: try/catch to handle permission shit?)
            rename(
                $uploadFolder.DIRECTORY_SEPARATOR.$folderBaseName,
                $targetFolder
            );

            // check if in database
            $params = [
                ":variant" => $detectedVariant,
                ":version" => $detectedVersion
            ];

            if(!is_null($subset)) {
                $params[':subset'] = $subset;
            }

            $inDB = rex_sql::factory()->getArray("
                SELECT
                    COUNT(*) AS cnt
                FROM
                    ".rex::getTable('fa_icons')."
                WHERE
                    variant = :variant AND
                    version = :version AND
                    subset ".(is_null($subset) ? 'IS NULL' : '= :subset'),
                $params
            );

            // insert
            if(!is_array($inDB) || (int)$inDB[0]['cnt'] == 0) {
                // extract icons
                $iconsMetaData = rex_string::yamlDecode(rex_file::get($targetFolder."/metadata/icons.yml"));

                foreach($iconsMetaData as $name => $params) {
                    foreach($params['styles'] as $weight) {
                        // get svg
                        $svg = null;

                        if(file_exists($targetFolder."/svgs/$weight/$name.svg")) {
                            $svg = rex_file::get($targetFolder."/svgs/$weight/$name.svg");
                        }

                        $sql = rex_sql::factory();
                        $sql->setTable(rex::getTable('fa_icons'));
                        $sql->setValues([
                            'name' => $name,
                            'code' => $params['unicode'],
                            'svg' => $svg,
                            'label' => $params['label'],
                            'searchterms' => json_encode($params['search']['terms']),
                            'weight' => strtoupper($weight[0]),
                            'variant' => $detectedVariant,
                            'version' => $detectedVersion,
                            'subset' => $subset,
                            'createuser' => rex::getUser()->getLogin()
                        ]);
                        $sql->insert();
                    }
                }
            }

            // fix css pathes for webfonts
            $allMinCSSPath = $targetFolder."/css/".rex_fa_iconpicker::ALL_MIN_CSS;

            if(!file_exists($allMinCSSPath)) {
                foreach(rex_fa_iconpicker::ALL_MIN_CSS_CUSTOMS as $versionCompare => $cssPath) {
                    preg_match("@^([<>=]*)(\d+.*)@", $versionCompare, $matches);

                    if(count($matches) == 3) {
                        if (rex_version::compare($detectedVersion, $matches[2], $matches[1])) {
                            $allMinCSSPath = $targetFolder."/css/".$cssPath;
                            break;
                        }
                    }
                }
            }

            $allMinCSS = rex_file::get($allMinCSSPath);

            $allMinCSS = preg_replace(
                "@url\(\.\.\/webfonts\/([a-z0-9\-\.]+)([^\)]+)?\)@i",
                "url(".rtrim(rex::getServer(), "/")."/index.php?rex_media_type=".rex_i18n::msg('fa_iconpicker_mm_fontsrc_name')."&rex_media_file=$1$2)",
                $allMinCSS,
                99
            );

            rex_file::put($allMinCSSPath, $allMinCSS);

            // clean
            if(!$config['keep-svgs'] && is_dir($targetFolder."/svgs")) {
                rex_dir::delete($targetFolder."/svgs");
            }
            if(!$config['keep-sprites'] && is_dir($targetFolder."/sprites")) {
                rex_dir::delete($targetFolder."/sprites");
            }
            if(!$config['keep-js'] && is_dir($targetFolder."/js")) {
                rex_dir::delete($targetFolder."/js");
            }
            if(!$config['keep-js-packages'] && is_dir($targetFolder."/js-packages")) {
                rex_dir::delete($targetFolder."/js-packages");
            }
            if(!$config['keep-less'] && is_dir($targetFolder."/less")) {
                rex_dir::delete($targetFolder."/less");
            }
            if(!$config['keep-scss'] && is_dir($targetFolder."/scss")) {
                rex_dir::delete($targetFolder."/scss");
            }
            if(!$config['keep-otfs'] && is_dir($targetFolder."/otfs")) {
                rex_dir::delete($targetFolder."/otfs");
            }
        }

        $latest = rex_fa_package::getLatestPackage();
        $latest->setActive();

        return $errors;
    }

    /**
     * @return string
     */
    public function getVariant(): string {
        return $this->variant;
    }

    /**
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getSubset(): ?string {
        return $this->subset;
    }

    /**
     * @return array
     */
    public function getWeights(): array {
        return $this->weights;
    }

    /**
     * check if package exists (file and db / has icons)
     *
     * @param rex_fa_package $package
     * @return bool
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function packageExists(rex_fa_package $package): bool {
        return file_exists(rex_fa_iconpicker::getCssPathSpecific(
            $package->getVariant(),
            $package->getVersion(),
            $package->getSubset()
        )) && count($package->getIcons()) > 0;
    }

    /**
     * @return array
     */
    public function getIcons(): array {
        return $this->icons;
    }

    /**
     * @param array $icons
     */
    public function setIcons(array $icons): void {
        $this->icons = $icons;
    }

    /**
     * @param $iconName
     * @return ?stdClass
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public function getIcon($iconName): ?stdClass {
        return isset($this->icons[$iconName]) ? $this->icons[$iconName] : null;
    }

    /**
     * @return bool
     */
    public function isInstalled() {
        return $this->installed;
    }

    /**
     * set active package
     *
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public function setActive() {
        rex_addon::get(self::PACKAGE)->setConfig("variant", $this->getVariant());
        rex_addon::get(self::PACKAGE)->setConfig("version", $this->getVersion());
        rex_addon::get(self::PACKAGE)->setConfig("subset", $this->getSubset());
    }

    /**
     * delete package (files and DB)
     *
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function delete() {
        if(rex_fa_package::packageExists($this)) {
            $uploadPath = self::PACKAGE_PATH."uploads".DIRECTORY_SEPARATOR.$this->getVariant()."-".$this->getVersion();
            $packagePath = self::PACKAGE_PATH.$this->getVariant().DIRECTORY_SEPARATOR.$this->getVariant()."-".$this->getVersion();

            if(!is_null($this->getSubset())) {
                $uploadPath .= "-".$this->getSubset();
                $packagePath .= "-".$this->getSubset();
            }

            // delete zip
            rex_file::delete(rex_path::data($uploadPath.".zip"));
            // delete folder
            rex_dir::delete(rex_path::data($packagePath));
            // clear database

            $params = [
                'variant' => $this->getVariant(),
                'version' => $this->getVersion()
            ];

            if(!is_null($this->getSubset())) {
                $params[':subset'] = $this->getSubset();
            }

            rex_sql::factory()->setQuery("
                DELETE FROM
                    ".rex::getTable('fa_icons')."
                WHERE
                    variant = :variant AND
                    version = :version AND
                    subset ".(is_null($this->getSubset()) ? 'IS NULL' : '= :subset'),
                $params
            );
        }
    }

    /**
     * get latest package (version first, variant second)
     *
     * @return rex_fa_package|null
     * @throws rex_sql_exception
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getLatestPackage(): ?rex_fa_package {
        $latest = rex_sql::factory()->getArray("
            SELECT
                version,
                variant,
                subset
            FROM
                ".rex::getTable('fa_icons')."
            ORDER BY
                FIELD(variant, 'pro', 'free'),
                CAST(REPLACE(version, '.', '') AS UNSIGNED) DESC,
                subset ASC
            LIMIT 1
        ");

        if(isset($latest[0]['variant'])) {
            $package = new rex_fa_package($latest[0]['variant'], $latest[0]['version'], $latest[0]['subset']);
            return $package;
        }

        return null;
    }
}