<?php

/**
 * Caching Wrapper around zfr-mailchimp guzzle client
 * @author Brice Burgess
 */
use ZfrMailChimp\Client\MailChimpClient;

class FlexiFormMailChimpClient
{

    protected $auto_cache = true;

    protected $api_key = null;

    protected $client = null;

    protected $debug_exceptions = false;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->client = new MailChimpClient($api_key);
    }

    public function __call($method, $arguments)
    {
        // do not make requests with an invalid key
        if ($method != 'ping' && ! $this->isApiKeyValid()) {
            return;
        }

        if ($this->getAutoCache()) {
            $cache_key = $this->makeCacheKey($method, $arguments);

            $cache = SS_Cache::factory(__CLASS__);
            if ($result = $cache->load($cache_key)) {
                return unserialize($result);
            }
        }

        try {
            $result = call_user_func_array(
                array(
                    $this->client,
                    $method
                ), $arguments);
        } catch (Exception $e) {
            if(Director::isDev() && $this->debug_exceptions) {
                var_dump($e);
            }
            $result = false;
        }

        if ($this->getAutoCache()) {
            $cache->save(serialize($result));
        }

        return $result;
    }

    public function setAutoCache(boolean $flag)
    {
        return $this->auto_cache = $flag;
    }

    public function getAutoCache()
    {
        return $this->auto_cache;
    }

    public function makeCacheKey($method, $arguments)
    {
        $key = $this->api_key . '_' . $method . '_' . md5(var_export($arguments, true));
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }


    public function getApiKey()
    {
        return $this->api_key;
    }

    public function isApiKeyValid()
    {
        if (empty($this->api_key) || stristr($this->api_key, '-') === false) {
            return false;
        }

        return ($this->__call('ping', array()));
    }
}