<?php
/**
 * PDO实例
 * @author Not well-known man
 */

class Database_PDO extends PDO
{

    protected $transactionCounter = 0;

    public function beginTransaction():bool
    {
        if (!$this->transactionCounter++) {
            return parent::beginTransaction();
        }
        $this->exec('save tran point' . $this->transactionCounter);
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
            $this->exec('rollback tran point' . ($this->transactionCounter + 1));
            return TRUE;
        }
        return parent::rollback();
    }

}