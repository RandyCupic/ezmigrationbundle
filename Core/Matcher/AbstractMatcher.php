<?php

namespace Kaliop\eZMigrationBundle\Core\Matcher;

use Kaliop\eZMigrationBundle\API\MatcherInterface;

abstract class AbstractMatcher implements MatcherInterface
{
    /** @var string[] $allowedConditions the keywords we allow to be used for matching on*/
    protected $allowedConditions = array();
    /** @var  string $returns user-readable name of the type of object returned */
    protected $returns;
    /** @var int $maxConditions the maximum number of conditions we allow to match on for a single match request */
    protected $maxConditions = 1;

    protected function validateConditions(array $conditions)
    {
        if (count($conditions) == 0) {
            throw new \Exception($this->returns . ' can not be matched because the matching conditions are empty');
        }

        if (count($conditions) > $this->maxConditions) {
            throw new \Exception($this->returns . " can not be matched because multiple matching conditions are specified. Only {$this->maxConditions} condition(s) are supported");
        }

        foreach ($conditions as $key => $value) {
            if (!in_array((string)$key, $this->allowedConditions)) {
                throw new \Exception($this->returns . " can not be matched because matching condition '$key' is not supported. Supported conditions are: " .
                    implode(', ', $this->allowedConditions));
            }
        }
    }

    protected function matchAnd($conditionsArray)
    {
        if (!is_array($conditionsArray) || !count($conditionsArray)) {
            throw new \Exception($this->returns . " can not be matched because no matching conditions found for 'and' clause.");
        }

        $class = null;
        foreach ($conditionsArray as $conditions) {
            $out = $this->match($conditions);
            if ($out instanceof \ArrayObject) {
                $class = get_class($out);
                $out = $out->getArrayCopy();
            }
            if (!isset($results)) {
                $results = $out;
            } else {
                $results = array_intersect_key($results, $out);
            }
        }

        if ($class) {
            $results = new $class($results);
        }

        return $results;
    }

    protected function matchOr(array $conditionsArray)
    {
        if (!is_array($conditionsArray) || !count($conditionsArray)) {
            throw new \Exception($this->returns . " can not be matched because no matching conditions found for 'or' clause.");
        }

        $class = null;
        $results = array();
        foreach ($conditionsArray as $conditions) {
            $out = $this->match($conditions);
            if ($out instanceof \ArrayObject) {
                $class = get_class($out);
                $out = $out->getArrayCopy();
            }
            $results = array_replace($results, $out);
        }

        if ($class) {
            $results = new $class($results);
        }

        return $results;
    }

    public function matchOne(array $conditions)
    {
        $results = $this->match($conditions);
        $count = count($results);
        if ($count !== 1) {
            throw new \Exception("Found $count " . $this->returns . " when expected exactly only one to match the conditions");
        }
        return reset($results);
    }

    abstract public function match(array $conditions);
}
