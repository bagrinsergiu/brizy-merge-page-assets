<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\AssetGroup;

class CompiledData
{
    private $data;

    public function __construct($compliedDataJson)
    {
        $this->data = json_decode($compliedDataJson, true) ?: [];
    }

    public function mergeBlocks(array $compiledData)
    {
        foreach ($compiledData['blocks'] as &$block) {
            if (empty($block['html'])) {
                $block['html']   = $this->getBlock($block['id'])['html'];
                $block['assets'] = $this->getBlock($block['id'])['assets'];
            }
        }

        $this->data['blocks'] = $compiledData['blocks'];

        return $this;
    }

    public function getBlocks()
    {
        if (isset($this->data['blocks'])) {
            return $this->data['blocks'];
        }

        return [];
    }

    public function hasBlocks(): bool
    {
        return isset($this->data['blocks']);
    }

    private function getBlock($id)
    {
        foreach ((array)$this->data['blocks'] as $block) {
            if ($block['id'] == $id) {
                return $block;
            }
        }

        return null;
    }

    private function deleteBlock($id)
    {
        foreach ((array)$this->data['blocks'] as $i => $block) {
            if ($block['id'] == $id) {
                $this->data['blocks'] = array_splice($this->data['blocks'], $i, 1);
            }
        }
    }

    private function updateBlock($id, $html, $assets)
    {
        foreach ((array)$this->data['blocks'] as $i => &$block) {
            if ($block['id'] == $id) {
                $block['html']   = $html;
                $block['assets'] = $assets;
            }
        }
    }

    public function asJson()
    {
        return json_encode($this->data);
    }

    /**
     * This method will return the block(s) html only.
     *
     * @return string
     */
    public function buildHtml()
    {
        if (isset($this->data['blocks'])) {
            return implode('', array_column($this->data['blocks'], 'html'));
        }

        return '';
    }

    /**
     * This method will return the block(s) HTML wrapped in page div and including global blocks placeholders.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->wrapHtml("{{ brizy_dc_global_blocks position=\"top\" }}{$this->buildHtml()}{{ brizy_dc_global_blocks position=\"bottom\" }}");
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public function wrapHtml($content)
    {
        $classAttr = empty($this->data['rootClassNames']) ? '' : ' class="'.implode(' ', $this->data['rootClassNames']).'"';
        $rootAttrs = $this->data['rootAttributes'] ?? [];
        $attrs     = array_reduce(
            array_keys($rootAttrs),
            function ($carry, $key) use ($rootAttrs) {
                return $carry.' '.$key.'="'.htmlspecialchars($rootAttrs[$key]).'"';
            },
            ''
        );
        $attrs = $attrs ? ' '.$attrs : '';

        return "<div$classAttr$attrs>$content</div>";
    }

    public function getScriptsAssetGroup(): array
    {
        if (empty($this->data['blocks'])) {
            return [];
        }

        $assets = [];

        foreach (array_column($this->data['blocks'], 'assets') as $groups) {
            foreach (array_intersect_key($groups, array_flip(['freeScripts', 'proScripts'])) as $asset) {
                $assets[] = AssetGroup::instanceFromJsonData($asset);
            }
        }

       return $assets;
    }

    public function getStylesAssetGroup(): array
    {
        if (empty($this->data['blocks'])) {
            return [];
        }

        $assets = [];

        foreach (array_column($this->data['blocks'], 'assets') as $groups) {
            foreach (array_intersect_key($groups, array_flip(['freeStyles', 'proStyles'])) as $asset) {
                $assets[] = AssetGroup::instanceFromJsonData($asset);
            }
        }

        return $assets;
    }
}