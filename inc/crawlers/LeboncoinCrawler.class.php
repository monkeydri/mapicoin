<?php

require_once __DIR__.'/Crawler.class.php';

class LeboncoinCrawler extends Crawler {

    public function __construct($url) {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        // Extract page parameter
        if (!$this->validateURL($url)) {
            return false;
        }
        $url = replace_get_parameter($url, 'o', self::$_PAGE_PATTERN);
        return parent::__construct($url);
    }

    protected function validateURL($url) {
        if (preg_match('#^https?://www\.leboncoin\.fr/.+#i', $url)) {
            return true;
        }
        return false;
    }

    /**
     * Return ad info from DOMElement (using xpath)
     * @return array
     */
    public function getAdInfo(DOMElement $domElement) {
        $return = [
            'url'           => null,
            'title'         => null,
            'picture'       => null,
            'picture_count' => null,
            'location'      => null,
            'price'         => null,
            'date'          => null,
            'pro'           => null,
        ];
        // url
        $tmp = $this->domXpath->query(
            './/a[@class="list_item clearfix trackable"]/@href',
            $domElement
        );
        $return['url'] = 'https:'.$tmp->item(0)->nodeValue;

        // title
        $tmp = $this->domXpath->query(
            './/h2[@class="item_title"]/text()',
            $domElement
        );
        $return['title'] = trim($tmp->item(0)->nodeValue);

        // picture
        $tmp = $this->domXpath->query(
            './/span[@class="lazyload"]/@data-imgsrc',
            $domElement
        );
        $return['picture'] = 'https:'.trim(@$tmp->item(0)->nodeValue ?? '//static.leboncoin.fr/img/no-picture.png');

        // picture_count
        $tmp = $this->domXpath->query(
            './/span[@class="item_imageNumber"]/span/text()',
            $domElement
        );
        $return['picture_count'] = trim(@$tmp->item(0)->nodeValue ?? 0);

        // pro
        $tmp = $this->domXpath->query(
            './/span[@class="ispro"]/text()',
            $domElement
        );
        $tmp = trim(@$tmp->item(0)->nodeValue ?? null);
        $return['pro'] = preg_replace('#\s+#i', ' ', $tmp);

        // location
        $tmp = $this->domXpath->query(
            '(.//p[@class="item_supp"])[2]/text()',
            $domElement
        );
        $tmp = trim($tmp->item(0)->nodeValue);
        $return['location'] = preg_replace('#\s+#i', ' ', $tmp);

        // price
        $tmp = $this->domXpath->query(
            './/h3[@class="item_price"]/text()',
            $domElement
        );
        $return['price'] = trim(@$tmp->item(0)->nodeValue ?? '');

        // date
        $tmp = $this->domXpath->query(
            './/aside[@class="item_absolute"]/p[@class="item_supp"]/text()',
            $domElement
        );
        $return['date']      = trim($tmp->item(0)->nodeValue);
        $return['timestamp'] = $this->convertDateToTimestamp($return['date']);

        return $return;
    }

    /**
     * Return DOMElements of all ads based on a xpath
     * @return array(DOMElement, ...)
     */
    public function getAds() {
        return $this->domXpath->query(
            '//section[@class="tabsContent block-white dontSwitch"]/ul/li'
        );
    }



    /**
     * Convert a leboncoin date to timestamp
     */
    private function convertDateToTimestamp($date) {
        $tmp = explode(',', $date);
        if (!isset($tmp[1])) {
            return false;
        }
        $jour  = trim(strtolower($tmp[0]));
        $heure = trim($tmp[1]);
        if ($jour == "aujourd'hui") {
            $jour = date('d F');
        } elseif ($jour == 'hier') {
            $jour = date('d F', strtotime('-1 day'));
        }

        // On converti les dates leboncoin en EN
        $replaces = [
            'janvier'   => 'january',
            'février'   => 'february',
            'mars'      => 'march',
            'avril'     => 'april',
            'mai'       => 'may',
            'juin'      => 'june',
            'juillet'   => 'july',
            'août'      => 'august',
            'septembre' => 'september',
            'octobre'   => 'october',
            'novembre'  => 'november',
            'décembre'  => 'december',
        ];

        $date = sprintf('%s %d %s', $jour, date('Y'), $heure);
        $date = str_ireplace(array_keys($replaces), array_values($replaces), $date);

        return strtotime($date);
    }
}