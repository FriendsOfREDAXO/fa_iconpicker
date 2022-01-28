<?php
/**
 * @author   Peter Schulze [p.schulze@bitshifters.de]
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_var_fa_iconpicker extends rex_var
{
    protected function getOutput()
    {
        // .rex_url::backend("data/addons/".rex_fa_package::PACKAGE."/packages/".rex_fa_iconpicker::getActiveVersion())
        $baseUrl = rex::getServer();

        if(rex_addon::get("yrewrite") && rex_addon::get("yrewrite")->isAvailable()) {
            $baseUrl = rex_yrewrite::getCurrentDomain()->getPath();
        }

        $css = '<link rel="stylesheet" href="'.$baseUrl.'media/'.rex_i18n::msg('fa_iconpicker_mm_name').'/'.rex_fa_iconpicker::getActiveCssFileName().'" type="text/css" media="screen,print" />';
        return self::quote($css);
    }
}