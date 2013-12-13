<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 13/12/2013
 * Time: 18:53
 */

namespace RBM\TaggedCache\Storage;


class FileStorage implements \Iterator, \ArrayAccess, \Countable
{

    protected $_cursor = 0;


    protected $_path;

    public function __construct($path)
    {
        $this->_path = $path;
    }

    protected function _f($index)
    {
        return $this->_d($index) . $index;
    }

    protected function _d($index = null)
    {
        return $this->_path . '/';
    }

    public function offsetExists($index)
    {
        return file_exists($this->_f($index));
    }

    public function offsetGet($index)
    {
        return unserialize(file_get_contents($this->_f($index)));
    }

    public function offsetSet($index, $newval)
    {
        file_put_contents($this->_f($index), serialize($newval));
    }

    public function offsetUnset($index)
    {
        $f = $this->_f($index);
        if (file_exists($f)) {
            unlink($f);
        }
    }

    public function count()
    {
        $f = new \FilesystemIterator($this->_d(), \FilesystemIterator::SKIP_DOTS);
        return iterator_count($f);
    }


    protected function _getFileAtIndex($index)
    {
        $h = opendir($this->_d());
        $i = 0;
        while (false !== ($entry = readdir($h))) {
            if($entry != '.' && $entry != '..') { //Skips over . and ..
                if($i == $index) return $entry;
                $i++;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->offsetGet($this->key());
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        ++$this->_cursor;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->_getFileAtIndex($this->_cursor);
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->offsetExists($this->key());
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->_cursor = 0;
    }


} 