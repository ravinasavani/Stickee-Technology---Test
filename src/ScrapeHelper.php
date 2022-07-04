<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function numOfPage(string $url)
    {
        $client = new Client();
        $response = $client->get($url);
        $document = new Crawler($response->getBody()->getContents(), $url);
        $numOfPage = $document->filter('#pages > div > a')->count();
        return $numOfPage;
    }
    public static function fetchDocument(string $url)
    {
        $client = new Client();
        $response = $client->get($url);
        $document = new Crawler($response->getBody()->getContents(), $url);
        $productObj = $document->filter('div.product > div')->each(function ($node) {
            if ($node->filter('h3')->first()->count()) {
                $title = $node->filter('h3')->first()->text();
            }
            if ($node->filter('h3 span.product-capacity')->count()) {
                $capacity = $node->filter('h3 span.product-capacity')->text();
                if (strpos($capacity, 'GB') !== false) {
                    $capacityMB = preg_split("/[GB\s]/", $capacity);
                    $capacityMB = (int)$capacityMB[0] * 1000;
                }
                if (strpos($capacity, 'MB') !== false) {
                    $capacityMB = preg_split("/[MB\s]/", $capacity);
                    $capacityMB = $capacityMB[0];
                }
            }
            if ($node->filter('img')->count()) {
                $image = $node->filter('img')->image()->getUri();
            }
            if ($node->filter('div div div')->count()) {
                $colour = $node->filter('div div div')->each(function ($colourNode, $j) {
                    return $colourNode->filter('span')->attr('data-colour');
                });
            }
            if ($node->filter('div')->eq($node->filter('div div div span')->count() + 3)->count()) {
                $price = $node->filter('div')->eq($node->filter('div div div span')->count() + 3)->text();
            }
            if ($node->filter('div')->eq($node->filter('div div div span')->count() + 4)->count()) {
                $availabilityText = $node->filter('div')->eq($node->filter('div div div span')->count() + 4)->text();
                if ($availabilityText == "Availability: Out of Stock") {
                    $isAvailable = 'false';
                } else {
                    $isAvailable = 'true';
                }
            }
            if ($node->filter('div')->last()->count()) {
                if ($node->filter('div')->last()->text() != $availabilityText) {
                    $shippingText = $node->filter('div')->last()->text();
                    if (preg_match('~[0-9]+~', $shippingText)) {
                        $pattern = "/(\d{1,2}[a-z]*\s[A-Za-z]{3}\s\d{4})|(\d{4}-\d{2}-\d{2})/";
                        preg_match_all($pattern, $shippingText, $matches);
                        $shippingDate = date('Y-m-d', strtotime($matches[0][0]));
                    } else if (strpos($shippingText, 'tomorrow') !== false) {
                        $shippingDate = date('Y-m-d', strtotime('tomorrow'));
                    } else {
                        $shippingDate = '';
                    }
                } else {
                    $shippingText = '';
                    $shippingDate = '';
                }
            }
            for ($j = 1; $j < count($colour); $j++) {
                $productByColour[] = [
                    'title' => $title,
                    'price' => $price,
                    'imageUrl' => $image,
                    'capacityMB' => $capacityMB,
                    'colour' => $colour[$j],
                    'availabilityText' => $availabilityText,
                    'isAvailable' => $isAvailable,
                    'shippingText' => $shippingText,
                    'shippingDate' => $shippingDate
                ];
            }
            $productObj = $productByColour;
            return $productObj;
        });
        $productArr = array();
        foreach ($productObj as $products) {
            foreach ($products as $product) {
                $productArr[] = $product;
            }
        }
        return $productArr;
    }
}