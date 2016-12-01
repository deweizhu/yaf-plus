<?php

class Topic_Topic_HitsPlugin
{
    private $topic, $cache;

    public function __construct($topic)
    {
        $this->topic = $topic;
        $this->cache = Cache::instance();
    }

    public function after_get()
    {
        $id = $this->topic->id;
        $hits = $this->cache->get('hits_' . $id);
        if ($hits !== FALSE) {
            $this->topic->data['hits'] = $hits[0];
        }
    }

    public function after_ls()
    {
        if ($this->topic->data) {
            foreach ($this->topic->data as &$v) {
                if (!isset($v['id']) || $v['id'] <= 0)
                    continue;
                $hits = $this->cache->get('hits_' . $v['id']);
                if ($hits !== FALSE) {
                    $v['hits'] = $hits[0];
                }
            }
        }
    }

    public function __call($method, $args)
    {
        return NULL;
    }

    public function __set($property, $value)
    {
        return NULL;
    }

    public function __get($property)
    {
        return NULL;
    }

    public function __toString()
    {
        return get_class($this);
    }
}