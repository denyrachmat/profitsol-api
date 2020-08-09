<?php

namespace App\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * Use this trait if your model has a composite primary key.
 * The primary key should then be an array with all applicable columns.
 * 
 * Class HasCompositeKey
 * @package App\Traits
 */
trait CompositeKey
{
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  Builder $query
     * @return Builder
     * @throws Exception
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        foreach ($this->getKeyName() as $key) {
            if ($this->$key)
                $query->where($key, '=', $this->$key);
            else
                throw new Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
        }

        return $query;
    }

    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

    public function getKey()
    {
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::getKey();
        }

        $pk = [];

        foreach($keys as $keyName){
            $pk[$keyName] = $this->getAttribute($keyName);
        }

        return $pk;
    }

    public function find($id, $columns = ['*'])
    {
        if (is_array($id) || $id instanceof Arrayable) {
            $out = null;
            foreach ($id as $key => $value) {
                //echo "{$key} => {$value} ";
                if ($out == null)
                {
                    $out = $this->where($key, $value);
                }
                else
                {
                    $out = $out->where($key, $value);
                }
            }

            return $out->first($columns);
        }

        return $this->whereKey($id)->first($columns);
    }
}
