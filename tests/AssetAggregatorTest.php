<?php

namespace BrizyMergeTests;

use BrizyMerge\AssetAggregator;
use BrizyMerge\Assets\AssetGroup;
use PHPUnit\Framework\TestCase;

class AssetAggregatorTest extends TestCase
{
    public function testGetAssetList()
    {
        $page = json_decode(file_get_contents("./tests/data/page.json"));
        $page2 = json_decode(file_get_contents("./tests/data/page2.json"));

        $assets   = [];
        $assets[] = AssetGroup::instanceFromJsonData($page->blocks->freeStyles);
        $assets[] = AssetGroup::instanceFromJsonData($page->blocks->proStyles);
        $assets[] = AssetGroup::instanceFromJsonData($page2->blocks->freeStyles);

        $aggregator = new AssetAggregator($assets);

        $aggregator->getAssetList();
    }

}
