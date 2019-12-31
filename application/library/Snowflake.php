<?php
/**
 * Twitter的分布式自增ID算法snowflake
 * (new Snowflake())->nextId();
 * @author Not well-known man
 */

class Snowflake
{
    //开始时间,固定一个小于当前时间的毫秒数即可
    const twepoch = 1576886400000;//2019/12/21 0:0:0

    //机器标识占的位数
    const workerIdBits = 10;

    //毫秒内自增数点的位数
    const sequenceBits = 12;

    protected $workId = 0;

    //要用静态变量
    static $lastTimestamp = -1;
    static $sequence = 0;


    function __construct(int $workId = 1023)
    {
        //机器ID范围判断
        $maxWorkerId = -1 ^ (-1 << self::workerIdBits);
        if ($workId > $maxWorkerId || $workId < 0) {
            throw new Exception("workerId can't be greater than " . $this->maxWorkerId . " or less than 0");
        }
        //赋值
        $this->workId = $workId;
    }

    //生成一个ID
    public function nextId()
    {
        $timestamp = $this->timeGen();
        $lastTimestamp = self::$lastTimestamp;
        //判断时钟是否正常
        if ($timestamp < $lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds", ($lastTimestamp - $timestamp));
        }
        //生成唯一序列
        if ($lastTimestamp == $timestamp) {
            $sequenceMask = -1 ^ (-1 << self::sequenceBits);
            self::$sequence = (self::$sequence + 1) & $sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
            }
        } else {
            self::$sequence = 0;
        }
        self::$lastTimestamp = $timestamp;
        //
        //时间毫秒/数据中心ID/机器ID,要左移的位数
        $timestampLeftShift = self::sequenceBits + self::workerIdBits;
        $workerIdShift = self::sequenceBits;
        //组合3段数据返回: 时间戳.工作机器.序列
        $nextId = (($timestamp - self::twepoch) << $timestampLeftShift) | ($this->workId << $workerIdShift) | self::$sequence;
        return $nextId;
    }

    //取当前时间毫秒
    protected function timeGen()
    {
        $timestamp = (float)sprintf("%.0f", microtime(TRUE) * 1000);
        return $timestamp;
    }

    //取下一毫秒
    protected function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

}