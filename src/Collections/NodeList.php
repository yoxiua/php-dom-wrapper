<?php

namespace DOMWrap\Collections;

use DOMWrap\Document;
use DOMWrap\Traits\TraversalTrait;
use DOMWrap\Traits\ManipulationTrait;
use Countable, ArrayAccess, RecursiveIterator, RecursiveIteratorIterator, Traversable;

/**
 * Node List
 *
 * @package DOMWrap\Collections
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3 Clause
 */
class NodeList implements Countable, ArrayAccess, RecursiveIterator
{
    use TraversalTrait;
    use ManipulationTrait {
        ManipulationTrait::__call as __manipulationCall;
    }

    /** @var array */
    protected $nodes = [];

    /** @var Document */
    protected $document;

    /**
     * @param Document $document
     * @param Traversable|array $nodes
     */
    public function __construct(Document $document, $nodes = null) {
        if (!$this->isArrayLike($nodes)) {
            $nodes = [];
        }

        $this->document = $document;

        foreach ($nodes as $node) {
            $this->nodes[] = $node;
        }
    }

    /**
     * @param string $name
     * @param mixed $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments) {
        try {
            $result = $this->__manipulationCall($name, $arguments);
        } catch (\BadMethodCallException $e) {
            if (!method_exists($this->first(), $name)) {
                throw new \BadMethodCallException("Call to undefined method " . get_class($this) . '::' . $name . "()");
            }

            $result = call_user_func_array([$this->first(), $name], $arguments);
        }

        return $result;
    }

    /**
     * @return self
     */
    public function collection() {
        return $this;
    }

    /**
     * @return \DOMDocument
     */
    public function document() {
        return $this->document;
    }

    /**
     * @param NodeList $nodeList
     *
     * @return NodeList
     */
    public function result($nodeList) {
        return $nodeList;
    }

    /**
     * @see Countable::count()
     *
     * @return int
     */
    public function count() {
        return count($this->nodes);
    }

    /**
     * @see ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->nodes[$offset]);
    }

    /**
     * @see ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset) {
        return isset($this->nodes[$offset]) ? $this->nodes[$offset] : null;
    }

    /**
     * @see ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->nodes[] = $value;
        } else {
            $this->nodes[$offset] = $value;
        }
    }

    /**
     * @see ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->nodes[$offset]);
    }

    /**
     * @see RecursiveIterator::RecursiveIteratorIterator()
     *
     * @return RecursiveIteratorIterator
     */
    public function getRecursiveIterator() {
        return new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * @see RecursiveIterator::getChildren()
     *
     * @return RecursiveIterator
     */
    public function getChildren() {
        $nodes = [];

        if ($this->valid()) {
            $nodes = $this->current()->childNodes;
        }

        return new static($nodes);
    }

    /**
     * @see RecursiveIterator::hasChildren()
     *
     * @return bool
     */
    public function hasChildren() {
        if ($this->valid()) {
            return $this->current()->hasChildNodes();
        }

        return false;
    }

    /**
     * @see RecursiveIterator::current()
     * @see Iterator::current()
     *
     * @return mixed
     */
    public function current() {
        return current($this->nodes);
    }

    /**
     * @see RecursiveIterator::key()
     * @see Iterator::key()
     *
     * @return mixed
     */
    public function key() {
        return key($this->nodes);
    }

    /**
     * @see RecursiveIterator::next()
     * @see Iterator::next()
     *
     * @return mixed
     */
    public function next() {
        return next($this->nodes);
    }

    /**
     * @see RecursiveIterator::rewind()
     * @see Iterator::rewind()
     *
     * @return mixed
     */
    public function rewind() {
        return reset($this->nodes);
    }

    /**
     * @see RecursiveIterator::valid()
     * @see Iterator::valid()
     *
     * @return bool
     */
    public function valid() {
        return key($this->nodes) !== null;
    }

    /**
     * @return NodeList
     */
    public function reverse() {
        array_reverse($this->nodes);

        return $this;
    }

    /**
     * @return mixed
     */
    public function first() {
        return $this->rewind();
    }

    /**
     * @return mixed
     */
    public function last() {
        return end($this->nodes);
    }

    /**
     * @param int $key
     *
     * @return mixed
     */
    public function get($key) {
        if (isset($this->nodes[$key])) {
            return $this->nodes[$key];
        }

        return null;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value) {
        $this->nodes[$key] = $value;
    }

    /**
     * @param \Closure $function
     *
     * @return self
     */
    public function each(\Closure $function) {
        foreach ($this->nodes as $node) {
            $result = $function($node);

            if ($result === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * @param \Closure $function
     *
     * @return NodeList
     */
    public function map(\Closure $function) {
        $results = [];

        foreach ($this->nodes as $node) {
            $result = $function($node);

            if (!is_null($result) && $result !== false) {
                $results[] = $result;
            }
        }

        return $this->newNodeList($results);
    }

    /**
     * @param \Closure $function
     *
     * @return mixed[]
     */
    public function reduce(\Closure $function, $initial = null) {
        return array_reduce($this->nodes, $function, $initial);
    }

    /**
     * @param $input mixed
     *
     * @return bool
     */
    public function isArrayLike($input) {
        if (is_array($input) || $input instanceof \Traversable) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function toArray() {
        return $this->nodes;
    }

    /**
     * @param \Traversable|array $nodes
     */
    public function fromArray($nodes = null) {
        if (!$this->isArrayLike($nodes)) {
            $nodes = [];
        }

        $this->nodes = $nodes;
    }

    /**
     * @param NodeList|array $elements
     *
     * @return NodeList
     */
    public function merge($elements = []) {
        if (!is_array($elements)) {
            $elements = $elements->toArray();
        }

        return $this->newNodeList(array_merge($this->toArray(), $elements));
    }

    /**
     * @param \DOMNode $node
     *
     * @return self
     */
    public function add(\DOMNode $node) {
        $this->nodes[] = $node;

        return $this;
    }

    /**
     * @param \DOMNode $node
     *
     * @return self
     */
    public function push(\DOMNode $node) {
        return $this->add($node);
    }

    /**
     * @return \DOMNode
     */
    public function pop() {
        return array_pop($this->nodes);
    }

    /**
     * @param \DOMNode $node
     *
     * @return self
     */
    public function unshift(\DOMNode $node) {
        array_unshift($this->nodes, $node);

        return $this;
    }

    /**
     * @return \DOMNode
     */
    public function shift() {
        return array_shift($this->nodes);
    }

    /**
     * @param \DOMNode $node
     *
     * @return bool
     */
    public function exists(\DOMNode $node) {
        return in_array($node, $this->nodes, true);
    }

    /**
     * @param \DOMNode $node
     *
     * @return self
     */
    public function delete(\DOMNode $node) {
        $index = array_search($node, $this->nodes, true);

        if ($index !== false) {
            unset($this->nodes[$index]);
        }

        return $this;
    }
}