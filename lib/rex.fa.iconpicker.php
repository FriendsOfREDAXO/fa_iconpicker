<?php
/**
 * @date     29.05.2020 12:28
 * @author   Peter Schulze [p.schulze@bitshifters.de]
 */

class rex_fa_iconpicker
{
    /**
     * @var string
     * free or pro
     */
    private static $activeVariant;

    /**
     * @var string | null
     * active version (for example 5.13.0)
     */
    private static $activeVersion;

    /**
     * @var string | null
     * active subset hash (sha1) [only filled if is subset / not full default package]
     */
    private static $activeSubset = null;

    /**
     * constants
     */
    const ALL_MIN_CSS = 'all.min.css';

    /**
     * for special/old versions
     */
    const ALL_MIN_CSS_CUSTOMS = [
        '<5.3' => 'all.css'
    ];

    /**
     * get active variant
     *
     * @return string
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getActiveVariant(): ?string {
        if(is_null(self::$activeVariant)) {
            self::$activeVariant = rex_addon::get(rex_fa_package::PACKAGE)->getConfig("variant");
        }
        
        return self::$activeVariant;
    }

    /**
     * get active version
     *
     * @return string
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getActiveVersion(): ?string {
        if(is_null(self::$activeVersion)) {
            self::$activeVersion = rex_addon::get(rex_fa_package::PACKAGE)->getConfig("version");
        }
    
        return self::$activeVersion;
    }

    /**
     * get active subset
     *
     * @return string
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getActiveSubset(): ?string {
        if(is_null(self::$activeSubset)) {
            self::$activeSubset = rex_addon::get(rex_fa_package::PACKAGE)->getConfig("subset");
        }

        return self::$activeSubset;
    }

    /**
     * get path to active package css file
     *
     * @param bool $startFromCMSRoot start from cms root instead of server root
     * @param bool $pathOnly
     * @return string|null
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getCssPath(bool $startFromCMSRoot = false, bool $pathOnly = false): ?string {
        return self::getCssPathSpecific(self::getActiveVariant(), self::getActiveVersion(), self::getActiveSubset(), $startFromCMSRoot, $pathOnly);
    }

    /**
     * get path to provided package css file
     *
     * @param string|null $variant
     * @param string|null $version
     * @param string|null $subset
     * @param bool $startFromCMSRoot start from cms root instead of server root
     * @param bool $pathOnly
     * @return string|null
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getCssPathSpecific(?string $variant, ?string $version, ?string $subset = null, bool $startFromCMSRoot = false, bool $pathOnly = false): ?string {
        $basePath = rex_fa_package::PACKAGE_PATH.$variant.DIRECTORY_SEPARATOR."$variant-$version";

        if(!is_null($subset)) {
            $basePath .= "-".$subset;
        }

        $path = (!$startFromCMSRoot ?
                    rex_path::data($basePath.DIRECTORY_SEPARATOR.(!$pathOnly ? self::getCSSVersionPath($variant, $version, $subset) : 'css'.DIRECTORY_SEPARATOR)) :
                    "redaxo".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR.$basePath.DIRECTORY_SEPARATOR.(!$pathOnly ? self::getCSSVersionPath($variant, $version, $subset) : 'css'.DIRECTORY_SEPARATOR)
                );

        return (file_exists($path) || $startFromCMSRoot ? $path : null);
    }

    /**
     * last part of CSS part, can differ depending on version
     *
     * @param string|null $variant
     * @param string|null $version
     * @param string|null $subset
     * @return string|null
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getCSSVersionPath(?string $variant, ?string $version, ?string $subset = null): ?string {
        if(is_null($variant) || is_null($version)) {
            return null;
        }

        $basePath = rex_fa_package::PACKAGE_PATH.$variant.DIRECTORY_SEPARATOR."$variant-$version";

        if(!is_null($subset)) {
            $basePath .= "-".$subset;
        }

        if(file_exists(rex_path::data($basePath.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.self::ALL_MIN_CSS))) {
            return 'css'.DIRECTORY_SEPARATOR.self::ALL_MIN_CSS;
        } else {
            foreach(rex_fa_iconpicker::ALL_MIN_CSS_CUSTOMS as $versionCompare => $cssPath) {
                preg_match("@^([<>=]*)(\d+.*)@", $versionCompare, $matches);

                if(count($matches) == 3) {
                    if (rex_version::compare($version, $matches[2], $matches[1])) {
                        $path = rex_path::data($basePath.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$cssPath);

                        if(file_exists($path)) {
                            return 'css'.DIRECTORY_SEPARATOR.$cssPath;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * get url for active package css file
     *
     * @return string|null
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getCssUrl(): ?string {
        return self::getCssUrlSpecific(
            self::getActiveVariant(),
            self::getActiveVersion(),
            self::getActiveSubset()
        );
    }

    /**
     * get active css file name
     *
     * @return string|null
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function getActiveCssFileName(): ?string {
        $basePath = rex_fa_package::PACKAGE_PATH.self::getActiveVariant().DIRECTORY_SEPARATOR.self::getActiveVariant()."-".self::getActiveVersion().
                    (self::getActiveSubset() != "" ? "-".self::getActiveSubset() : "");

        if(file_exists(rex_path::data($basePath.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.self::ALL_MIN_CSS))) {
            return self::ALL_MIN_CSS;
        } else {
            foreach(rex_fa_iconpicker::ALL_MIN_CSS_CUSTOMS as $versionCompare => $cssPath) {
                preg_match("@^([<>=]*)(\d+.*)@", $versionCompare, $matches);

                if(count($matches) == 3) {
                    if (rex_version::compare(self::getActiveVersion(), $matches[2], $matches[1])) {
                        $path = rex_path::data($basePath.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$cssPath);

                        if(file_exists($path)) {
                            return $cssPath;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * get url for provided package css file
     *
     * @param string $variant
     * @param string $version
     * @param string|null $subset
     * @return string
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getCssUrlSpecific(string $variant, string $version, ?string $subset = null): ?string {
        $basePath = rex_fa_package::PACKAGE_PATH.$variant.DIRECTORY_SEPARATOR."$variant-$version";

        if(!is_null($subset)) {
            $basePath .= "-".$subset;
        }

        return rex_url::backend("data".DIRECTORY_SEPARATOR.$basePath.DIRECTORY_SEPARATOR.self::getCSSVersionPath($variant, $version, $subset));
    }

    /**
     * get set of icons for given page (from database, no objects created)
     *
     * @param int $page frontend calculates desired page by scroll position
     * @param bool $brandIcons
     * @return array
     * @throws rex_sql_exception
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getIconsFromDB(int $page = 0, bool $brandIcons = false): array {
        if(is_null(self::getActiveVersion())) {
            return [];
        }

        $params = [
            ":variant"  => self::getActiveVariant(),
            ":version"  => self::getActiveVersion()
        ];

        if(!is_null(self::getActiveSubset())) {
            $params[':subset'] = self::getActiveSubset();
        }

        $icons = rex_sql::factory()->getArray("
            SELECT id,name,code
            FROM ".rex::getTable('fa_icons')."
            WHERE variant = :variant AND version = :version AND isbrand = :isbrand AND subset ".(is_null(self::getActiveSubset()) ? "IS NULL" : " = :subset")."
            ORDER BY `name` ASC
        ", $params);

        return $icons;
    }

    /**
     * @param int $page frontend calculates desired page by scroll position
     * @param bool $brandIcons
     * @return array
     * @throws rex_sql_exception
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function getIcons(int $page = 0, bool $brandIcons = false): array {
        $icons = new rex_fa_package();
        $icons->getIcons();
    }

    /**
     * get packages
     *
     * @param string|null $type
     * @return array
     * @throws rex_sql_exception
     * @author Peter Schulze [p.schulze@bitshifters.de]
     */
    public static function getPackages(string $type = null): array {
        $where = '';
        $params = [];

        if($type != null) {
            $where = 'HAVING variant = :variant';
            $params[':variant'] = $type;
        }

        $packagesRaw = rex_sql::factory()->getArray("
            SELECT
                version,
                variant,
                subset,
                CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED) AS major_version,
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version, '.', 2), '.', -1) AS UNSIGNED) AS minor_version,
                CAST(SUBSTRING_INDEX(version, '.', -1) AS UNSIGNED) AS built_version
            FROM
                ".rex::getTable('fa_icons')." 
            GROUP BY
                version,
                variant,
                subset
            $where
            ORDER BY
                FIELD(variant, 'pro', 'free'),
                CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED) DESC,
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version, '.', 2), '.', -1) AS UNSIGNED) DESC,
                CAST(SUBSTRING_INDEX(version, '.', -1) AS UNSIGNED) DESC,
                subset ASC
        ", $params);

        $packages = [];

        foreach($packagesRaw as $pack) {
            $p = new rex_fa_package($pack['variant'], $pack['version'], $pack['subset']);

            $packages[] = $p;
        }

        return $packages;
    }

    /**
     * fixing src:url() pathes for given package
     *
     * @param string|null $variant
     * @param string|null $version
     * @param string|null $subset
     * @return bool
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function fixFontSrcInCss(?string $variant, ?string $version, ?string $subset = null):bool {

    }

    /**
     * fixing src:url() pathes in active package
     *
     * @return bool
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function fixFontSrcInActiveCss():bool {
        return self::fixFontSrcInCss(self::getActiveVariant(), self::getActiveVersion(), self::getActiveSubset());
    }
}