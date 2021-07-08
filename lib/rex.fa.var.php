<?php
/**
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_var_fa_iconpicker extends rex_var
{
    protected function getOutput()
    {
        // .rex_url::backend("data/addons/".rex_fa_package::PACKAGE."/packages/".rex_fa_iconpicker::getActiveVersion())
        $css = '<link rel="stylesheet" href="'.rex_fa_iconpicker::getCssUrl().'" type="text/css" media="screen,print" />';
        return self::quote($css);
    }
}