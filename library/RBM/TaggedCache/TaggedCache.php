<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 13/12/2013
 * Time: 18:49
 */

namespace RBM\TaggedCache;


class TaggedCache
{

    /**
     * @var \ArrayObject
     */
    public $dataStore = [];
    /**
     * @var \ArrayObject
     */
    public $tagsStore = [];
    /**
     * @var \ArrayObject
     */
    public $indexStore = [];

    public function save($id, $data, $lifetime = 0, $tags = [])
    {
        if (!is_string($id) || $id == '' || substr($id, 0, 1) == '|') {
            throw new Exception('Invalid entry id : must be a string, not begin with the character | (pipe) nor be empty');
        }

        if($index = $this->_getIndex($id)){
            $this->_removeEntry($index);
            $this->_removeIndex($index);
        }

        $death = time() + intval($lifetime);
        $dataKey = $this->_getDataKey($death . '|' . $id);
        $this->indexStore[$this->_getIndexKey($id)] = $dataKey;
        $this->dataStore[$dataKey] = $data;

        foreach ($tags as $tag) {
            $ids = $this->_getTaggedEntries($tag);
            $ids[] = $id;
            $this->tagsStore[$this->_getTagKey($tag)] = $ids;
        }
    }

    /**
     * @param $id
     * @param bool $returnIfNotExistsOrObsolete
     * @return bool|mixed
     */
    public function get($id, $returnIfNotExistsOrObsolete = false)
    {
        // the index looks like e24920940$id
        $index = $this->_getIndex($id);

        // no key
        if (is_null($index)) {
            return $returnIfNotExistsOrObsolete;
        }

        // obsolete ?
        if (time() >= substr($index, 1, strpos($index, '|'))) {
            $this->_removeIndex($id);
            $this->_removeEntry($index);
            return $returnIfNotExistsOrObsolete;
        }

        // test in case the index is outdated ? maybe recompile indexes or throw exception
        return isset($this->dataStore[$index]) ? $this->dataStore[$index] : $returnIfNotExistsOrObsolete;
    }

    /**
     * @param $id
     * @return null
     */
    protected function _getIndex($id)
    {
        $xk = $this->_getIndexKey($id);
        return isset($this->indexStore[$xk]) ? $this->indexStore[$xk] : null;
    }

    /**
     * @param $tag
     * @return array
     */
    protected function _getTaggedEntries($tag)
    {
        $tk = $this->_getTagKey($tag);
        return isset($this->tagsStore[$tk]) ? $this->tagsStore[$tk] : [];
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        $index = $this->_getIndex($id);
        $this->_removeIndex($id);
        $this->_removeEntry($index);
    }

    /**
     * @param $tag
     * @return array
     */
    public function getByTag($tag)
    {
        $results = [];

        $tk = $this->_getTagKey($tag);

        if (!isset($this->tagsStore[$tk])) return [];

        $toRemove = [];

        foreach ($this->tagsStore[$tk] as $id) {
            if ($entry = $this->get($id)) {
                $results[$id] = $entry;
            } else {
                $id = null;
                $toRemove[] = $id;
            }
        }

        return $results;
    }

    /**
     * @param $tag
     */
    public function removeByTag($tag)
    {
        foreach ($this->_getTaggedEntries($tag) as $id) {
            $this->remove($id);
        }
        $this->_removeTag($tag);
    }


    /**
     * @param $index
     */
    protected function _removeEntry($index)
    {
        if (isset($this->dataStore[$index])) unset($this->dataStore[$index]);
    }

    /**
     * @param $id
     */
    protected function _removeIndex($id)
    {
        $xk = $this->_getIndexKey($id);
        if (isset($this->indexStore[$xk])) unset($this->indexStore[$xk]);
    }

    /**
     * @param $index
     */
    protected function _removeTag($tag)
    {
        $tk = $this->_getTagKey($tag);
        if (isset($this->tagsStore[$tk])) unset($this->tagsStore[$tk]);
    }

    /**
     * @param $tag
     * @return string
     */
    protected function _getTagKey($tag)
    {
        return 't' . $tag;
    }

    /**
     * @param $id
     * @return string
     */
    protected function _getIndexKey($id)
    {
        return 'x' . $id;
    }

    /**
     * @param $id
     * @return string
     */
    protected function _getDataKey($id)
    {
        return 'e' . $id;
    }

    /**
     *
     */
    public function dump()
    {
        return [
            "index" => $this->indexStore,
            "tags" => $this->tagsStore,
            "data" => $this->dataStore,
        ];
    }

    public function clean()
    {

    }

} 