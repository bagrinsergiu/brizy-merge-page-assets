<?php

namespace BrizyMergeTests;

use BrizyMerge\Assets\Asset;
use BrizyMerge\Assets\AssetFont;
use PHPUnit\Framework\TestCase;

class AssetFontTest extends TestCase
{
    public function test_instanceFromJsonData()
    {
        $data = [
            "name" => "main",
            "score" => 30,
            "content" => [
                "type" => "file",
                "url" => "https://fonts.googleapis.com/css?family=Lato:100,100italic,300,300italic,regular,italic,700,700italic,900,900italic|Overpass:100,100italic,200,200italic,300,300italic,regular,italic,600,600italic,700,700italic,800,800italic,900,900italic|Red+Hat+Text:regular,italic,500,500italic,700,700italic|DM+Serif+Text:regular,italic|Blinker:100,200,300,regular,600,700,800,900|Aleo:300,300italic,regular,italic,700,700italic|Nunito:200,200italic,300,300italic,regular,italic,600,600italic,700,700italic,800,800italic,900,900italic|Knewave:regular|Palanquin:100,200,300,regular,500,600,700|Palanquin+Dark:regular,500,600,700|Roboto:100,100italic,300,300italic,regular,italic,500,500italic,700,700italic,900,900italic|Oswald:200,300,regular,500,600,700|Oxygen:300,regular,700|Playfair+Display:regular,italic,700,700italic,900,900italic|Fira+Sans:100,100italic,200,200italic,300,300italic,regular,italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic|Abril+Fatface:regular|Comfortaa:300,regular,500,600,700|Kaushan+Script:regular|Noto+Serif:regular,italic,700,700italic|Montserrat:100,100italic,200,200italic,300,300italic,regular,italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&subset=arabic,bengali,cyrillic,cyrillic-ext,devanagari,greek,greek-ext,gujarati,hebrew,khmer,korean,latin-ext,tamil,telugu,thai,vietnamese&display=swap",
                "attr" => [
                    "class" => "brz-link brz-link-google",
                    "type" => "text/css",
                    "rel" => "stylesheet"
                ]
            ],
            "pro" => false,
            "type" => "type",
        ];

        $asset = AssetFont::instanceFromJsonData($data);

        $this->assertEquals($data['name'], $asset->getName(), 'It should return the correct value for name');
        $this->assertEquals($data['score'], $asset->getScore(), 'It should return the correct value for score');
        $this->assertEquals($data['content']['type'], $asset->getType(), 'It should return the correct value for type');
        $this->assertEquals($data['content']['url'], $asset->getUrl(), 'It should return the correct value for url');
        $this->assertEquals($data['content']['attr'], $asset->getAttrs(), 'It should return the correct value for attrs');
        $this->assertEquals($data['pro'], $asset->isPro(), 'It should return the correct value for pro');
        $this->assertEquals($data['type'], $asset->getFontType(), 'It should return the correct value for type');

    }

    public function test_instanceFromJsonData_exceptions1()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name" => "main",
            "score" => 30,
            "content" => "content",
            "pro" => false,
            "type" => "type",
            "additional_key" => ""
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions2()
    {
        $this->expectException(\Exception::class);

        $data = [
            "name" => "main",
            "score" => 30,
            "content" => "content",
            "type" => "type",
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }

    public function test_instanceFromJsonData_exceptions3()
    {
        $this->expectException(\Exception::class);

        $data = [
            "score" => 30,
            "content" => "content",
            "type" => "type",
            "pro" => false,
        ];

        $asset = AssetFont::instanceFromJsonData($data);
    }
}
