<?php
/**
 *  GPS定位类
 *
 * @author    知名不具
 */
class Helper_Gps
{

    const EARTH_RADIUS = 6371; //地球半径，平均半径为6371km

    /**
     *计算某个经纬度的周围某段距离的正方形的四个点
     *
     *  用法：使用此函数计算得到结果后，带入sql查询。
     *   $squares = squarePoint($lng, $lat);
     *   $info_sql = "SELECT * from `tablename` where lat<>0
     *   AND lat>{$squares['right-bottom']['lat']} AND lat<{$squares['left-top']['lat']}
     *   AND lng>{$squares['left-top']['lng']} AND lng<{$squares['right-bottom']['lng']} "
     *  在字段lat和lng上建立一个联合索引后，使用此项查询，每条记录的查询消耗平均为0.8毫秒
     * @param float lng  经度
     * @param float lat  纬度
     * @param float distance  该点所在圆的半径，该圆与此正方形内切，默认值为10千米
     * @return array 正方形的四个点的经纬度坐标
     */
    public static function squarePoint($lng, $lat, $distance = 10)
    {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);

        $dlat = $distance / self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);

        return array(
            'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
            'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
            'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
            'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng)
        );
    }

    /**
     *  @desc 根据两点间的经纬度计算距离
     *  @param float $lng 经度值
     *  @param float $lat 纬度值
     */
    public static function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $earthRadius = 6367000; //approximate radius of earth in meters

        /*
          Convert these degrees to radians
          to work with the formula
        */

        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;

        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;

        /*
          Using the
          Haversine formula

          http://en.wikipedia.org/wiki/Haversine_formula

          calculate the distance
        */

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    /**
     * getDistance MYSQL函数：
     *
        DELIMITER $$
        DROP FUNCTION IF EXISTS `getDistance`$$
        CREATE DEFINER=`root`@`%` FUNCTION `getDistance`( lon1 FLOAT(10,7), lat1 FLOAT(10,7), lon2 FLOAT(10,7), lat2 FLOAT(10,7)) RETURNS DOUBLE
        READS SQL DATA
        COMMENT '根据经纬度计算距离'
        BEGIN
        DECLARE d DOUBLE;
        DECLARE radius INT;
        SET radius = 6378140; #假设地球为正球形，直径为6378140米S
        SET d = (2*ATAN2(SQRT(SIN((lat1-lat2)*PI()/180/2)
         *SIN((lat1-lat2)*PI()/180/2)+
        COS(lat2*PI()/180)*COS(lat1*PI()/180)
         *SIN((lon1-lon2)*PI()/180/2)
         *SIN((lon1-lon2)*PI()/180/2)),
        SQRT(1-SIN((lat1-lat2)*PI()/180/2)
         *SIN((lat1-lat2)*PI()/180/2)
        +COS(lat2*PI()/180)*COS(lat1*PI()/180)
         *SIN((lon1-lon2)*PI()/180/2)
         *SIN((lon1-lon2)*PI()/180/2))))*radius;
        RETURN d;
        END$$
        DELIMITER ;
     */
}