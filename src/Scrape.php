<?php
namespace App;
error_reporting(0);
require_once __DIR__.'/../vendor/autoload.php';
class Scrape
{
    private array $products = [];
    private $numOfPage = 1;

    public function run(): void
    {
        $this->numOfPage = ScrapeHelper::numOfPage("https://www.magpiehq.com/developer-challenge/smartphones");
        for($i = 1; $i <= $this->numOfPage; $i++)
        {
            $productsPerPage = ScrapeHelper::fetchDocument("https://www.magpiehq.com/developer-challenge/smartphones/?page=$i");
            $this->products = array_merge($this->products,$productsPerPage);
        }
        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

$scrape = new Scrape();
$scrape->run();