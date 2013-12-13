<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 14/12/2013
 * Time: 00:13
 */

namespace RBM\TaggedCache\Storage;


class SQLite implements \Iterator, \ArrayAccess, \Countable
{

    protected $_pdo;

    protected $_cursor;

    public function __construct($filename)
    {
        $this->_pdo = new \PDO('sqlite:' . $filename);
        $this->_pdo->exec("CREATE TABLE IF NOT EXISTS storage (id TEXT PRIMARY KEY, data BLOB)");
        // don't verify data on disk
        $this->_pdo->exec("PRAGMA synchronous = OFF");
        // turn off rollback
        $this->_pdo->exec("PRAGMA journal_mode = OFF");
        // peridically clean the database
        $this->_pdo->exec("PRAGMA auto_vacuum = INCREMENTAL");
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $stmt = $this->_pdo->query("SELECT data FROM storage LIMIT 1 OFFSET {$this->_cursor}");
        return $stmt->fetchColumn();
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
        $stmt = $this->_pdo->query("SELECT id FROM storage LIMIT 1 OFFSET {$this->_cursor}");
        return $stmt->fetchColumn();
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->_cursor > 0 && $this->_cursor < $this->count();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->_cursor = 0;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        $stmt = $this->_pdo->prepare("SELECT id FROM storage WHERE id = ?");
        $stmt->execute([$offset]);
        return ($stmt->fetchColumn() != false);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        $stmt = $this->_pdo->prepare("SELECT data FROM storage WHERE id = ?");
        $stmt->execute([$offset]);
        if ($data = $stmt->fetchColumn()) {
            return unserialize($data);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $stmt = $this->_pdo->prepare("INSERT OR REPLACE INTO storage(id, data) VALUES (?, ?)");
        $stmt->execute([$offset, serialize($value)]);


    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $stmt = $this->_pdo->prepare("DELETE FROM storage WHERE id = ?");
        $stmt->execute([$offset]);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        $stmt = $this->_pdo->query("SELECT COUNT(1) FROM storage");
        return $stmt->fetchColumn();
    }

    public function test(){
        $stmt = $this->_pdo->query("SELECT * FROM storage");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
} 