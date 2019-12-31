<?php
/**
 * PDO实例
 * @author Not well-known man
 */

class Database_PDOMySQL extends \PDO
{

    protected $transactionCounter = 0;

    public function beginTransaction():bool
    {
        if (!$this->transactionCounter++) {
            return parent::beginTransaction();
        }
        $this->exec('SAVEPOINT point' . $this->transactionCounter);
        return $this->transactionCounter >= 0;
    }

    public function commit():bool
    {
        if (!--$this->transactionCounter) {
            return parent::commit();
        }
        return $this->transactionCounter >= 0;
    }

    public function rollback():bool
    {
        if (--$this->transactionCounter) {
            $this->exec('ROLLBACK TO SAVEPOINT point' . ($this->transactionCounter + 1));
            return TRUE;
        }
        return parent::rollback();
    }

}