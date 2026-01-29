<?php

namespace BrizyMergeTests;

use BrizyMerge\AssetAggregator;
use BrizyMerge\Assets\AssetGroup;
use PHPUnit\Framework\TestCase;

class AssetAggregatorTest extends TestCase
{
    public function testGetAssetList()
    {
        $page = json_decode(file_get_contents("/opt/project/tests/data/page.json"), true);
        $page2 = json_decode(file_get_contents("/opt/project/tests/data/page2.json"), true);

        $assets = [];
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['freeStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page['blocks']['proStyles']);
        $assets[] = AssetGroup::instanceFromJsonData($page2['blocks']['freeStyles']);

        $aggregator = new AssetAggregator($assets);

        $list = $aggregator->getAssetList();

        $this->assertCount(7,$list,'Assert that it returns 8 assets.');

        $score = 0;
        foreach ($list as $i => $item) {
            if ($i == 0) {
                $score = $item->getScore();
                continue;
            }

            $this->assertGreaterThanOrEqual($score, $item->getScore(), 'The items are not sorted ascending');

            $score = $item->getScore();
        }
    }



    public function test_instanceFromJsonHugeData()
    {
        $this->markTestSkipped('This test is with big client data');

        $compiledData = file_get_contents('/opt/project/tests/data/A.json');

        $t = microtime(true);

        $compiledData = new CompiledData($compiledData);

       // $assetAggregator1 = new AssetAggregator($compiledData->getScriptsAssetGroup());
        //$assetAggregator1->getAssetList();
        echo "Execution time: " . (microtime(true) - $t) . " seconds;\n";

        $t = microtime(true);
        $assetAggregator2 = new AssetAggregator($compiledData->getStylesAssetGroup());
        $assetAggregator2->getAssetList();

        echo "Execution time: " . (microtime(true) - $t) . " seconds;";

        $t = 0;

    }
}


