<?php

namespace ThemesFirst\Plugin\I2Azon;

if (!class_exists('ThemesFirst\Plugin\I2Azon\API_Search')) {
    class API_Search
    {

        protected $ids;
        protected $keyword;
        protected $sortBy;
        // searchIndex categoryId
        protected $si;
        protected $page;
        protected $rating;
        protected $minPrice;
        protected $maxPrice;
        protected $minSavingPercent;

        protected $tag;
        protected $accessKey;
        protected $secretKey;

        protected $store;
        protected $host;
        protected $region;
        protected $uriPath;
        //payload full
        protected $plfull;
        protected $amztarget;

        protected $debug = I2_AZON_DEBUG;

        public function __construct($type,  $data)
        {
            switch ($type) {
                case 'id':
                    $this->ids = $data;
                    $this->uriPath = "/paapi5/getitems";
                    $this->amztarget = "com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems";
                    $this->plfull = true;
                    break;
                case 'test':
                    $this->keyword  = 'laptop';
                    $this->sortBy   = 'Featured';
                    $this->si       = 'all';
                    $this->rating   = 1;
                    $this->page     = 1;
                    $this->minPrice = 0;
                    $this->maxPrice = 0;
                    $this->minSavingPercent = 0;
                    $this->uriPath = "/paapi5/searchitems";
                    $this->amztarget = "com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems";
                    $this->plfull = false;
                    break;
                default:
                    $this->keyword  = $data['term'];
                    $this->sortBy   = $data['sortBy'];
                    $this->si       = $data['searchIndex'];
                    $this->rating   = $data['rating'] < 1 ? 1 : $data['rating'];
                    $this->page     = $data['page'] + 1;
                    $this->minPrice = $data['minPrice'];
                    $this->maxPrice = $data['maxPrice'];
                    $this->minSavingPercent = $data['minSavingPercent'];
                    $this->uriPath = "/paapi5/searchitems";
                    $this->amztarget = "com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems";
                    $this->plfull = true;
                    break;
            }

            if ($type != 'test') {
                $options = get_option('i2_azon_options', i2_azon_options_default());
                $this->store = $options['amz_store'];
                $this->tag = $options['partner_tag'];
                $this->accessKey = $options['access_key'];
                $this->secretKey = $options['secret_key'];
            }
        }

        public function setKeysForValidate($amz_store, $partner_tag, $access_key, $secret_key)
        {
            $this->store = $amz_store;
            $this->tag = $partner_tag;
            $this->accessKey = $access_key;
            $this->secretKey = $secret_key;
        }

        private function validate()
        {

            if (strlen($this->tag) < 2) {
                return false;
            }
            return true;
        }
        public function get_items()
        {
            if ($this->validate() == false) {
                $msg = array('Message' =>  __('Please enter api key to fetch data from amazon.', 'themesfirst'));
                if ($this->debug) {
                    $msg['keys'] = array('tag' => $this->tag, 'accessKey' => $this->accessKey, 'secretKey' => $this->secretKey, 'store' => $this->store);
                }

                return json_encode(array("Errors" => array($msg)));
            }


            // $region = "us-east-1";
            $this->prepare_options();
            $payload = $this->plfull === true ? $this->get_full_payload() :  $this->get_payload();
            $region  = $this->region;
            $host    =  $this->host;
            $uriPath = $this->uriPath;

            $serviceName = "ProductAdvertisingAPI";

            $awsv5 = new AWS_V5($this->accessKey, $this->secretKey);
            $awsv5->setRegionName($region);
            $awsv5->setServiceName($serviceName);
            $awsv5->setPath($uriPath);
            $awsv5->setPayload($payload);
            $awsv5->setRequestMethod("POST");
            $awsv5->addHeader('content-encoding', 'amz-1.0');
            $awsv5->addHeader('content-type', 'application/json; charset=utf-8');
            $awsv5->addHeader('host', $host);
            $awsv5->addHeader('x-amz-target', $this->amztarget);
            $headers = $awsv5->getHeaders();


            $url = 'https://' . $host . $uriPath;

            $data = array(
                'method'      => 'POST',
                'timeout'     => 15,
                'headers'     => $headers,
                'body'        => $payload
            );

            $request = wp_remote_post($url, $data);

            // in case of error return false
            if (is_wp_error($request)) {
                return json_encode(array("Errors" => array(array('Message' =>  __($request->get_error_message(), 'themesfirst')))));
            }

            //print_r($request);

            $data = wp_remote_retrieve_body($request);
            return $data;
            //  return json_encode($data);



        }

        protected function get_payload()
        {
            //  $searchIndex = $this->si != null && $this->si != 'null' ? " \"SearchIndex\": \"{$this->si}\"," : "";
            $searchIndex = $this->si != null && $this->si != 'null' && $this->si != 'all' ? " \"SearchIndex\": \"{$this->si}\"," : "";
            $itemPage = $this->page < 11 && $this->page > 1 ? " \"ItemPage\": {$this->page}," : "";
            $minPrice = $this->minPrice > 0 ? " \"MinPrice\": {$this->minPrice}," : "";
            $maxPrice = $this->maxPrice > 0 ? " \"MaxPrice\": {$this->maxPrice}," : "";
            $minSavingPercent = $this->minSavingPercent > 0 ? " \"MinSavingPercent\": {$this->minSavingPercent}," : "";
            $sortBy = strlen($this->sortBy) > 0 ? " \"SortBy\": \"{$this->sortBy}\"," : "";
            $rating = $this->rating > 0 ? " \"MinReviewsRating\": {$this->rating}," : "";

            $term = empty($this->ids) ? " \"Keywords\": \"{$this->keyword}\"," : " \"ItemIds\": ["  . "  \"{$this->prepare_ids()}\"" . " ],";

            return "{"
                //  ." \"Keywords\": \"{$this->keyword}\","
                .  $term
                . " \"Resources\": ["
                . " \"SearchRefinements\","
                . " \"BrowseNodeInfo.BrowseNodes.SalesRank\","
                . " \"BrowseNodeInfo.WebsiteSalesRank\","
                . " \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
                . " \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
                . " \"Images.Primary.Large\","
                . " \"ItemInfo.Title\","
                . " \"Offers.Listings.Price\","
                . "  \"Offers.Listings.Promotions\","
                . "  \"Offers.Listings.SavingBasis\","
                . "  \"Offers.Summaries.HighestPrice\","
                . "  \"Offers.Summaries.LowestPrice\","
                . "  \"Offers.Summaries.OfferCount\""
                . " ],"
                .  $searchIndex
                // . " \"SortBy\": \"{$this->sortBy}\","
                // . " \"MinReviewsRating\": {$this->rating},"
                .  $sortBy . $rating
                . " \"Condition\": \"New\","
                .  $itemPage . $minPrice . $maxPrice . $minSavingPercent
                . " \"ItemCount\": 10,"
                . " \"PartnerTag\": \"{$this->tag}\","
                . " \"PartnerType\": \"Associates\","
                . " \"Marketplace\": \"www.amazon.{$this->store}\""
                . "}";
        }

        protected function get_full_payload()
        {

            $searchIndex = $this->si != null && $this->si != 'null' && $this->si != 'all' ? " \"SearchIndex\": \"{$this->si}\"," : "";
            $itemPage = $this->page < 11 && $this->page > 1 ? " \"ItemPage\": {$this->page}," : "";
            $minPrice = $this->minPrice > 0 ? " \"MinPrice\": {$this->minPrice}," : "";
            $maxPrice = $this->maxPrice > 0 ? " \"MaxPrice\": {$this->maxPrice}," : "";
            $minSavingPercent = $this->minSavingPercent > 0 ? " \"MinSavingPercent\": {$this->minSavingPercent}," : "";
            $sortBy = strlen($this->sortBy) > 0 ? " \"SortBy\": \"{$this->sortBy}\"," : "";
            $rating = $this->rating > 0 ? " \"MinReviewsRating\": {$this->rating}," : "";

            $term = empty($this->ids) ? " \"Keywords\": \"{$this->keyword}\"," : " \"ItemIds\": ["  . "  \"{$this->prepare_ids()}\"" . " ],";
            return "{"
                //   . " \"ItemIds\": ["
                //   . "  \"{$this->prepare_ids()}\""
                //   . " ],"
                . $term
                . " \"Resources\": ["
                . "  \"BrowseNodeInfo.BrowseNodes\","
                . "  \"BrowseNodeInfo.BrowseNodes.Ancestor\","
                . "  \"BrowseNodeInfo.BrowseNodes.SalesRank\","
                . "  \"BrowseNodeInfo.WebsiteSalesRank\","
                . "  \"CustomerReviews.Count\","
                . "  \"CustomerReviews.StarRating\","
                //   . "  \"Images.Primary.Small\","
                //   . "  \"Images.Primary.Medium\","
                . "  \"Images.Primary.Large\","
                //   . "  \"Images.Variants.Small\","
                //   . "  \"Images.Variants.Medium\","
                . "  \"Images.Variants.Large\","
                . "  \"ItemInfo.ByLineInfo\","
                . "  \"ItemInfo.ContentInfo\","
                . "  \"ItemInfo.ContentRating\","
                . "  \"ItemInfo.Classifications\","
                . "  \"ItemInfo.ExternalIds\","
                . "  \"ItemInfo.Features\","
                . "  \"ItemInfo.ManufactureInfo\","
                . "  \"ItemInfo.ProductInfo\","
                . "  \"ItemInfo.TechnicalInfo\","
                . "  \"ItemInfo.Title\","
                . "  \"ItemInfo.TradeInInfo\","
                . "  \"Offers.Listings.Availability.MaxOrderQuantity\","
                . "  \"Offers.Listings.Availability.Message\","
                . "  \"Offers.Listings.Availability.MinOrderQuantity\","
                . "  \"Offers.Listings.Availability.Type\","
                . "  \"Offers.Listings.Condition\","
                . "  \"Offers.Listings.Condition.SubCondition\","
                . "  \"Offers.Listings.DeliveryInfo.IsAmazonFulfilled\","
                . "  \"Offers.Listings.DeliveryInfo.IsFreeShippingEligible\","
                . "  \"Offers.Listings.DeliveryInfo.IsPrimeEligible\","
                . "  \"Offers.Listings.DeliveryInfo.ShippingCharges\","
                . "  \"Offers.Listings.IsBuyBoxWinner\","
                . "  \"Offers.Listings.LoyaltyPoints.Points\","
                . "  \"Offers.Listings.MerchantInfo\","
                . "  \"Offers.Listings.Price\","
                . "  \"Offers.Listings.ProgramEligibility.IsPrimeExclusive\","
                . "  \"Offers.Listings.ProgramEligibility.IsPrimePantry\","
                . "  \"Offers.Listings.Promotions\","
                . "  \"Offers.Listings.SavingBasis\","
                . "  \"Offers.Summaries.HighestPrice\","
                . "  \"Offers.Summaries.LowestPrice\","
                . "  \"Offers.Summaries.OfferCount\","
                . "  \"ParentASIN\","
                . "  \"RentalOffers.Listings.Availability.MaxOrderQuantity\","
                . "  \"RentalOffers.Listings.Availability.Message\","
                . "  \"RentalOffers.Listings.Availability.MinOrderQuantity\","
                . "  \"RentalOffers.Listings.Availability.Type\","
                . "  \"RentalOffers.Listings.BasePrice\","
                . "  \"RentalOffers.Listings.Condition\","
                . "  \"RentalOffers.Listings.Condition.SubCondition\","
                . "  \"RentalOffers.Listings.DeliveryInfo.IsAmazonFulfilled\","
                . "  \"RentalOffers.Listings.DeliveryInfo.IsFreeShippingEligible\","
                . "  \"RentalOffers.Listings.DeliveryInfo.IsPrimeEligible\","
                . "  \"RentalOffers.Listings.DeliveryInfo.ShippingCharges\","
                . "  \"RentalOffers.Listings.MerchantInfo\""
                . " ],"
                .  $searchIndex
                //         . " \"SortBy\": \"{$this->sortBy}\","
                //         . " \"MinReviewsRating\": {$this->rating},"
                .  $sortBy . $rating
                . " \"Condition\": \"New\","
                .  $itemPage . $minPrice . $maxPrice . $minSavingPercent
                . " \"ItemCount\": 10,"
                . " \"PartnerTag\": \"{$this->tag}\","
                . " \"PartnerType\": \"Associates\","
                . " \"Marketplace\": \"www.amazon.{$this->store}\""
                . "}";
        }
        protected function prepare_ids()
        {
            return implode("\",\"", $this->ids);
        }

        public function prepare_options()
        {
            /*
         * Source: https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
         *
            Australia   	webservices.amazon.com.au	us-west-2
            Brazil	        webservices.amazon.com.br	us-east-1
            Canada	        webservices.amazon.ca	us-east-1
            France	        webservices.amazon.fr	eu-west-1
            Germany	        webservices.amazon.de	eu-west-1
            India	        webservices.amazon.in	eu-west-1
            Italy	        webservices.amazon.it	eu-west-1
            Japan	        webservices.amazon.co.jp	us-west-2
            Mexico	        webservices.amazon.com.mx	us-east-1
            Netherlands	    webservices.amazon.nl	eu-west-1
            Singapore	    webservices.amazon.sg	us-west-2
            Saudi Arabia	webservices.amazon.sa	eu-west-1
						Spain	        webservices.amazon.es	eu-west-1
						Sweden			  webservices.amazon.se eu-west-1
            Turkey	        webservices.amazon.com.tr	eu-west-1
            UAE         	webservices.amazon.ae	eu-west-1
            United Kingdom	webservices.amazon.co.uk	eu-west-1
            United States	webservices.amazon.com	us-east-1         
         */

            switch ($this->store) {
                    // Australia
                case 'com.au':
                    $host = 'webservices.amazon.com.au';
                    $region = 'us-west-2';
                    break;
                    // Brazil
                case 'com.br':
                    $host = 'webservices.amazon.com.br';
                    $region = 'us-east-1';
                    break;
                    // Canada
                case 'ca':
                    $host = 'webservices.amazon.ca';
                    $region = 'us-east-1';
                    break;
                    // France
                case 'fr':
                    $host = 'webservices.amazon.fr';
                    $region = 'eu-west-1';
                    break;
                    // Germany
                case 'de':
                    $host = 'webservices.amazon.de';
                    $region = 'eu-west-1';
                    break;
                    // India
                case 'in':
                    $host = 'webservices.amazon.in';
                    $region = 'eu-west-1';
                    break;
                    // Italy
                case 'it':
                    $host = 'webservices.amazon.it';
                    $region = 'eu-west-1';
                    break;
                    // Japan
                case 'co.jp':
                    $host = 'webservices.amazon.co.jp';
                    $region = 'us-west-2';
                    break;
                    // Mexico
                case 'com.mx':
                    $host = 'webservices.amazon.com.mx';
                    $region = 'us-east-1';
                    break;
                    // Netherlands
                case 'nl':
                    $host = 'webservices.amazon.nl';
                    $region = 'eu-west-1';
                    break;
                    // Singapore
                case 'sg':
                    $host = 'webservices.amazon.sg';
                    $region = 'us-west-2';
                    break;
                    // Saudi Arabia
                case 'sa':
                    $host = 'webservices.amazon.sa';
                    $region = 'eu-west-1';
                    break;
                    // Spain
                case 'es':
                    $host = 'webservices.amazon.es';
                    $region = 'eu-west-1';
                    break;
                    // Turkey
                case 'com.tr':
                    $host = 'webservices.amazon.com.tr';
                    $region = 'eu-west-1';
                    break;
                    // United Arab Emirates
                case 'ae':
                    $host = 'webservices.amazon.ae';
                    $region = 'eu-west-1';
                    break;
                    // United Kingdom
                case 'co.uk':
                    $host = 'webservices.amazon.co.uk';
                    $region = 'eu-west-1';
                    break;
                    // Default (United States)
                default:
                    $host = 'webservices.amazon.com';
                    $region = 'us-east-1';
                    $store = 'com';
                    break;
            }
            // Set host
            $this->host = $host;
            // Set region
            $this->region = $region;
            // Set store
            $this->store = empty($this->store) ? $store : $this->store;
        }
    }
}
