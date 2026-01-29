<?php

namespace BrizyMerge;


use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetFont;
use BrizyMerge\Assets\AssetGroup;
use BrizyMerge\Assets\AssetLib;

class AssetAggregator
{
    const FONT_TYPE_GOOGLE = 'google-font';
    const FONT_TYPE_UPLOADED = 'uploaded-font';

    /**
     * @var AssetGroup[] $groups ;
     */
    private $groups;

    /**
     * AssetAggregator constructor.
     *
     * @param $assets
     */
    public function __construct($assets = [])
    {
        $this->groups = $assets;
    }

    /**
     * @param $group
     */
    public function addAssetGroup(AssetGroup $group)
    {
        $this->groups[] = $group;
    }

    /**
     * @param AssetGroup[] $groups
     */
    public function setAssetGroups($groups)
    {
        $this->groups = $groups;
    }


    /**
     * This will return a list of assets ready to be included in page
     *
     * @return Asset[]
     */
    public function getAssetList()
    {
        $assets = $this->getAggregatedAssets($this->groups);

        list($freeLibMap, $proLibMap) = $this->getLibMaps($this->groups);

        $assets = $this->normalizeAssets($assets, $freeLibMap, $proLibMap);

        return $this->sortAssets($assets);
    }

    private function getLibMaps($groups)
    {
        $maxFreeGroup = null;
        $maxFreeVersion = null;
        $maxProGroup = null;
        $maxProVersion = null;

        // Single pass: find max version for each type
        foreach ($groups as $g) {
            $main = $g->getMain();
            if (!$main) {
                continue;
            }

            $version = $g->getVersion();

            if ($main->isPro()) {
                if ($maxProVersion === null || version_compare($version, $maxProVersion) > 0) {
                    $maxProGroup = $g;
                    $maxProVersion = $version;
                }
            } else {
                if ($maxFreeVersion === null || version_compare($version, $maxFreeVersion) > 0) {
                    $maxFreeGroup = $g;
                    $maxFreeVersion = $version;
                }
            }
        }

        return [
            $maxFreeGroup ? $maxFreeGroup->getLibsMap() : null,
            $maxProGroup ? $maxProGroup->getLibsMap() : null,
        ];
    }

    /**
     * @param AssetGroup[] $groups
     * @return array
     */
    private function getAggregatedAssets($groups)
    {
        $assets = [];

        foreach ($groups as $group) {
            $main = $group->getMain();
            if ($main !== null) {
                $assets[$main->getName()] = $main;
            }

            foreach ($group->getGeneric() as $asset) {
                $assets[$asset->getName()] = $asset;
            }
            foreach ($group->getPageFonts() as $font) {
                $assets[$font->getName()] = $font;
            }
            foreach ($group->getPageStyles() as $style) {
                $assets[$style->getName()] = $style;
            }

            $selectors = $group->getLibsSelectors();
            $selectorsCount = count($selectors);

            if ($selectorsCount != 0) {
                foreach ($group->getLibsMap() as $alib) {
                    if (count(array_intersect($alib->getSelectors(), $selectors)) == $selectorsCount) {
                        $assets[] = $alib;
                        break;
                    }
                }
            }
        }

        return $assets;
    }

    private function normalizeAssets($assets, $freeLibMap, $proLibMap)
    {
        $duplicateKeys = [];
        $seen = [];

        foreach ($assets as $key => $val) {
            $hash = serialize($val);

            if (!isset($seen[$hash])) {
                $seen[$hash] = true;
            } else {
                $duplicateKeys[] = $key;
            }
        }

        foreach ($duplicateKeys as $key) {
            unset($assets[$key]);
        }

        // find libs and check if cannot be replace with a bigger lib to save requests
        $freeLibsFoundKeys = [];
        $freeLibsSelectorsFound = [];
        $proLibsFoundKeys = [];
        $proLibsSelectorsFound = [];

        foreach ($assets as $key => $lib) {
            if (!($lib instanceof AssetLib)) {
                continue;
            }

            if ($lib->isPro()) {
                $proLibsFoundKeys[] = $key;
                foreach ($lib->getSelectors() as $selector) {
                    $proLibsSelectorsFound[$selector] = $selector;
                }
            } else {
                $freeLibsFoundKeys[] = $key;
                foreach ($lib->getSelectors() as $selector) {
                    $freeLibsSelectorsFound[$selector] = $selector;
                }
            }
        }

        $assets = $this->groupLibs($assets, $freeLibMap, $freeLibsSelectorsFound, $freeLibsFoundKeys);
        $assets = $this->groupLibs($assets, $proLibMap, $proLibsSelectorsFound, $proLibsFoundKeys);

        $assets = $this->groupGoogleFonts($assets);
        $assets = $this->groupUploadedFonts($assets);

        return array_values($assets);
    }

    private function groupLibs($assets, $libMap, $selectorsFound, $foundLibPositions)
    {
        if (count($foundLibPositions) != 0) {
            // try to find a lib containing all found selectors
            //$libsSelectorsFound = array_unique($selectorsFound);
            $libsSelectorsFoundCount = count($selectorsFound);

            foreach ($libMap as $alib) {
                if (count(array_intersect($alib->getSelectors(), $selectorsFound)) == $libsSelectorsFoundCount) {

                    foreach ($foundLibPositions as $key) {
                        unset($assets[$key]);
                    }

                    $assets[] = $alib;
                    break;
                }
            }
        }

        return $assets;
    }

    private function groupGoogleFonts($assets)
    {
        return $this->groupFonts(
            $assets,
            self::FONT_TYPE_GOOGLE,
            "/\?family=(.*?)(&|\")/",
            function ($value, $matchTermination) {
                return "?family={$value}{$matchTermination}";
            }
        );
    }

    private function groupUploadedFonts($assets)
    {
        return $this->groupFonts(
            $assets,
            self::FONT_TYPE_UPLOADED,
            "/-font=(.*?)(&|\"|$)/",
            function ($value, $matchTermination) {
                return "-font={$value}{$matchTermination}";
            }
        );
    }

    private function groupFonts($assets, $fontType, $extractRegex, $replaceRegex)
    {
        // extract google fonts
        $fonts = [];
        $sampleFont = null;
        $matchTermination = "";
        foreach ($assets as $i => $asset) {
            /**
             * @var AssetFont $asset ;
             */
            if ($asset instanceof AssetFont && $asset->getFontType() === $fontType) {

                // obtain a font copy
                if (!$sampleFont) {
                    $sampleFont = $asset;
                }
                $matches = [];
                preg_match($extractRegex, $asset->getContentByType(), $matches);

                if (isset($matches[1])) {
                    $fontString = urldecode($matches[1]);
                    $fontSets = explode('|', $fontString);

                    foreach ($fontSets as $set) {
                        list($family, $weights) = explode(':', $set);
                        $weights = explode(',', $weights);

                        if (!isset($fonts[$family])) {
                            $fonts[$family] = [];
                        }

                        $fonts[$family] = array_merge($fonts[$family], $weights);
                    }
                }

                unset($assets[$i]);

                if (isset($matches[2])) {
                    $matchTermination = $matches[2];
                }
            }
        }

        // generate font query value
        if (!$sampleFont) {
            return $assets;
        }

        $f = [];
        foreach ($fonts as $family => $weight) {
            $weight = array_unique($weight);
            $f[] = $family . ':' . implode(',', $weight);
        }
        $fontQueryValue = implode('|', $f);

        $replaceValue = $replaceRegex($fontQueryValue, $matchTermination);

        $sampleFont->setUrl(
            preg_replace($extractRegex, $replaceValue, $sampleFont->getUrl())
        );

        $assets[] = $sampleFont;

        return array_values($assets);
    }

    private function sortAssets($assets)
    {
        if (empty($assets)) {
            return $assets;
        }

        // Group assets by score
        $buckets = [];
        foreach ($assets as $asset) {
            $score = $asset->getScore();
            if (!isset($buckets[$score])) {
                $buckets[$score] = [];
            }
            $buckets[$score][] = $asset;
        }

        // Sort bucket keys and flatten
        ksort($buckets);
        $sorted = [];
        foreach ($buckets as $bucket) {
            foreach ($bucket as $asset) {
                $sorted[] = $asset;
            }
        }

        return $sorted;
    }

}
