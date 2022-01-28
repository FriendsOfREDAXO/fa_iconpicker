<?php

/**
 * @author   Peter Schulze [p.schulze@bitshifters.de]
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_effect_font_awesome_path extends rex_effect_abstract
{
    public function execute()
    {
        $media = $this->media;
        $media->setMediaPath(rex_fa_iconpicker::getCssPath(false, true).$this->media->getMediaFilename());
        $media->setHeader('Content-Type', 'text/css');
    }

    public function getName()
    {
        return rex_i18n::msg('fa_iconpicker_mm_dropdown_name');
    }
}

class rex_effect_font_awesome_fontsrc_path extends rex_effect_abstract
{
    public function execute()
    {
        $fileEnding = strtolower(preg_replace("@.+\.([a-z0-9]+)$@i", "$1", $this->media->getMediaFilename()));

        switch($fileEnding) {
            case 'woff': $contentType = 'font/woff'; break;
            case 'woff2': $contentType = 'font/woff2'; break;
            case 'eot': $contentType = 'application/vnd.ms-fontobject'; break;
            case 'ttf': $contentType = 'application/font-sfnt'; break;
            case 'svg': $contentType = 'image/svg+xml'; break;
        }

        $basePath = preg_replace("@\/css\/?$@", "/webfonts/", rex_fa_iconpicker::getCssPath(false, true));

        $media = $this->media;
        $media->setMediaPath($basePath.$this->media->getMediaFilename());
        $media->setHeader('Content-Type', $contentType);
    }

    public function getName()
    {
        return rex_i18n::msg('fa_iconpicker_mm_fontsrc_dropdown_name');
    }
}

