<?php

namespace App\Twig\Extension;

use App\Entity\Posts;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('decode_html', [$this, 'decodeHtmlEntities']),
            new TwigFilter('apply_image_sizes', [$this, 'applyImageSizes'], ['is_safe' => ['html']]),
        ];
    }

    public function decodeHtmlEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function applyImageSizes(string $content, Posts $post): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            foreach ($post->getMetas() as $meta) {
                if (strpos($meta->getMetaKey(), 'image_size_') === 0 && strpos($meta->getMetaKey(), md5($src)) !== false) {
                    $sizes = json_decode($meta->getMetaValue(), true);
                    if (!empty($sizes['width'])) {
                        $img->setAttribute('width', $sizes['width']);
                    }
                    if (!empty($sizes['height'])) {
                        $img->setAttribute('height', $sizes['height']);
                    }
                }
            }
        }

        return $dom->saveHTML();
    }
}
