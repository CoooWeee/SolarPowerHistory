<?php

/**
 * This class is designed to provide a simplified interface to cURL which maintains cookies.
 *
 * @author Cobi Carter
 *
 * @see http://en.wikipedia.org/wiki/User:ClueBot/Source#Classes_.28wikibot.classes.php.29
 **/
class HttpRequester {
    private $ch;
    private $cookieFilename;
    public $postfollowredirs;
    public $getfollowredirs;

    /**
     * Our constructor function.  This just does basic cURL initialization.
     * @return void
     **/
    public function __construct () {
        global $proxyhost, $proxyport, $proxytype;

        $this->ch = curl_init();

        $this->cookieFilename = tempnam('tmp', 'solarbot.cookies');

        curl_setopt($this->ch,CURLOPT_COOKIEJAR,$this->cookieFilename);
        curl_setopt($this->ch,CURLOPT_COOKIEFILE,$this->cookieFilename);

        curl_setopt($this->ch,CURLOPT_MAXCONNECTS,100);
       // curl_setopt($this->ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
        curl_setopt($this->ch,CURLOPT_USERAGENT,'ClueBot/1.1');

        if (isset($proxyhost) and isset($proxyport) and ($proxyport != null) and ($proxyhost != null)) {
            curl_setopt($this->ch,CURLOPT_PROXYTYPE,isset( $proxytype ) ? $proxytype : CURLPROXY_HTTP);
            curl_setopt($this->ch,CURLOPT_PROXY,$proxyhost);
            curl_setopt($this->ch,CURLOPT_PROXYPORT,$proxyport);
        }
        $this->postfollowredirs = 0;
        $this->getfollowredirs = 1;
    }

    /**
     * Post to a URL.
     * @param $url The URL to post to.
     * @param $data The post-data to post, should be an array of key => value pairs.
     * @return Data retrieved from the POST request.
     **/
    public function post ($url,$data) {
        $time = microtime(1);
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->postfollowredirs);
        curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
        curl_setopt($this->ch,CURLOPT_HEADER,0);
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
        curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($this->ch,CURLOPT_POST,1);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS, $data);
				curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        // curl_setopt($this->ch,CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($this->ch,CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache",
								'Content-Type: application/x-www-form-urlencoded'
				));
        $data = curl_exec($this->ch);
        global $logfd; if (!is_resource($logfd)) $logfd = fopen('php://stderr','w'); fwrite($logfd,'POST: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n");
        return $data;
    }

    /**
     * Get a URL.
     * @param $url The URL to get.
     * @return Data retrieved from the GET request.
     **/
    public function get ($url) {
        $time = microtime(1);
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->getfollowredirs);
        curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
        curl_setopt($this->ch,CURLOPT_HEADER,0);
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
        curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
				curl_setopt($this->ch,CURLOPT_HTTPGET,1);
				curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($this->ch,CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache"
        ));
				$data = curl_exec($this->ch);
        global $logfd; if (!is_resource($logfd)) $logfd = fopen('php://stderr','w'); fwrite($logfd,'GET: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n");
        return json_decode($data, true);
    }

    /**
     * Our destructor.  Cleans up cURL and unlinks temporary files.
     **/
    public function __destruct () {
        curl_close($this->ch);
        @unlink($this->cookieFilename);
    }
}

?>