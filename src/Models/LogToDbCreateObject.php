<?php

namespace danielme85\LaravelLogToDB\Models;

/**
 * Trait LogToDbCreateObject
 *
 * @package danielme85\LaravelLogToDB
 */
trait LogToDbCreateObject
{
    /**
     * Create a new log object
     *
     * @param array $record
     * @param bool $detailed
     *
     * @return mixed
     */
    public function generate(array $record, bool $detailed = false)
    {
        if (isset($record['message'])) {
            $this->message = $record['message'];
        }
        if ($detailed) {
            if (isset($record['context'])) {
                if (!empty($record['context'])) {
                    $this->context = $record['context'];
                }
            }
        }
        if (isset($record['level'])) {
            $this->level = $record['level'];
        }
        if (isset($record['level_name'])) {
            $this->level_name = $record['level_name'];
        }
        if (isset($record['channel'])) {
            $this->channel = $record['channel'];
        }
        if (isset($record['datetime'])) {
            $this->datetime = $record['datetime'];
        }
        if (isset($record['extra'])) {
            if (!empty($record['extra'])) {
                $this->extra = $record['extra'];
            }
        }
        $this->unix_time = time();

        return $this;
    }

    /**
     * Context Accessor
     *
     * @param $value
     * @return null|array
     */
    public function getContextAttribute($value)
    {
        return $this->jsonDecodeIfNotEmpty($value);
    }

    /**
     * Extra Accessor
     *
     * @param $value
     * @return null|array
     */
    public function getExtraAttribute($value)
    {
        return $this->jsonDecodeIfNotEmpty($value);
    }

    /**
     * Context Mutator
     *
     * @param array $value
     */
    public function setContextAttribute(array $value)
    {
        if (isset($value['exception'])) {
            if (!empty($value['exception'])) {
                $exception = $value['exception'];
                if (get_class($exception) === \Exception::class
                    || is_subclass_of($exception, \Exception::class)) {
                    $newexception = [];
                    $newexception['class'] = get_class($exception);
                    $newexception['message'] = $exception->getMessage();
                    $newexception['code'] = $exception->getCode();
                    $newexception['file'] = $exception->getFile();
                    $newexception['line'] = $exception->getLine();
                    $newexception['trace'] = $exception->getTrace();
                    $newexception['previous'] = $exception->getPrevious();

                    $value['exception'] = $newexception;
                }
            }
        }
        $this->attributes['context'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * DateTime Mutator
     *
     * @param object $value
     */
    public function setDatetimeAttribute(object $value)
    {
        $this->attributes['datetime'] = $value->format(config('logtodb.datetime_format'));
    }

    /**
     * Extra Mutator
     *
     * @param array $value
     */
    public function setExtraAttribute($value)
    {
        $this->attributes['extra'] = $this->jsonEncodeIfNotEmpty($value);
    }

    /**
     * Encode to json if not empty/null
     *
     * @param $value
     * @return string
     */
    private function jsonEncodeIfNotEmpty($value)
    {
        if (!empty($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Decode from json if not empty/null
     *
     * @param $value
     * @param bool $arraymode
     * @return mixed
     */
    private function jsonDecodeIfNotEmpty($value, $arraymode = true)
    {
        if (!empty($value)) {
            return json_decode($value, $arraymode);
        }

        return $value;
    }

    /**
     * Delete the oldest records based on unix_time, silly spelling version.
     *
     * @param int $max amount of records to keep
     * @return bool
     */
    public function removeOldestIfMoreThen(int $max)
    {
        return $this->removeOldestIfMoreThan($max);
    }

    /**
     * Delete the oldest records based on unix_time
     *
     * @param int $max amount of records to keep
     * @return bool success
     */
    public function removeOldestIfMoreThan(int $max)
    {
        $current = $this->count();
        if ($current > $max) {
            $keepers = $this->orderBy('unix_time', 'DESC')->take($max)->pluck($this->primaryKey)->toArray();
            if ($this->whereNotIn($this->primaryKey, $keepers)->get()->each->delete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete records based on date, silly spelling version.
     *
     * @param string $datetime date supported by strtotime: http://php.net/manual/en/function.strtotime.php
     * @return bool success
     */
    public function removeOlderThen(string $datetime)
    {
        return $this->removeOlderThan($datetime);
    }

    /**
     * Delete records based on date.
     *
     * @param string $datetime date supported by strtotime: http://php.net/manual/en/function.strtotime.php
     * @return bool success
     */
    public function removeOlderThan(string $datetime)
    {
        $unixtime = strtotime($datetime);
        $deletes = $this->where('unix_time', '<=', $unixtime)->get();

        if (!$deletes->isEmpty()) {
            if ($deletes->each->delete()) {
                return true;
            }
        }

        return false;
    }

}
