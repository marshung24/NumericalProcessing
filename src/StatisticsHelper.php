<?php

namespace marsapp\helper\statistics;

use marsapp\helper\myarray\ArrayHelper;

/**
 * Statistics Helper
 * 
 * Provide numerical processing functions to assist users in statistics or charting.
 * 
 * ## 資料處理-級距調整
 * ### 方式：
 * - 以指定數為級距，將被處理數轉換為級距值
 * - 處理方式為
 *   - floor: 不足者為前一級
 *   - ceil: 超過者為後一級
 *   - round: 中點以後為下一級，不到中點為前一級
 *   > 預設 round
 * 
 * ## 資料處理-級距統計
 * 
 * ## 平均數、中位數、標準差
 * 
 * 
 * @depends marsapp/arrayhelper (composer)
 */
class StatisticsHelper
{

    /**
     * Options
     */
    protected static $_options = [
        // Auto scale options
        'autoScale' => [
            'method' => 'value',
            'step' => 1,
            'type' => 'round',
        ],
    ];

    /**
     * Temporary cache
     * @var array
     */
    protected static $_cache = [];

    /**
     * ********************************************
     * ************** Scale Function **************
     * ********************************************
     */

    /**
     * Get the scale level
     *
     * If value is 61, scale is 7, when type is floor/ceil/round:
     * - Scale Level: 8/9/9
     * - Scale Value: 56/63/63
     * 
     * @param float $value
     * @param float $step interval of two levels
     * @param string $type Processing method: floor,ceil,round(default)
     * @return float
     */
    public static function scaleLevel(float $value, float $step, $type = 'round')
    {
        switch ($type) {
            default:
            case 'floor':
                $opt = floor($value / $step);
                break;
            case 'ceil':
                $opt = ceil($value / $step);
                break;
            case 'round':
                $opt = round($value / $step);
                break;
        }

        return $opt;
    }

    /**
     * Get the scale value
     * 
     * If value is 61, scale is 7, when type is floor/ceil/round:
     * - Scale Level: 8/9/9
     * - Scale Value: 56/63/63
     * 
     * @param float $value
     * @param float $step interval of two levels
     * @param string $type Processing method: floor,ceil,round(default)
     * @return float
     */
    public static function scaleValue(float $value, float $step, $type = 'round')
    {
        return self::scaleLevel($value, $step, $type) * $step;
    }

    /**
     * Set auto scale options
     * 
     * This options will be used when auto scaling is required
     *
     * @param string $method how to scall scaleValue,scaleLevel
     * @param float $step interval of two levels
     * @param string $type Processing method: floor,ceil,round(default)
     * @return StatisticsHelper
     */
    public static function setAutoScale(string $method = 'scaleValue', float $step = 1, $type = 'round')
    {
        self::$_options['autoScale']['method'] = $method;
        self::$_options['autoScale']['step'] = $step;
        self::$_options['autoScale']['type'] = $type;

        return new static();
    }

    /**
     * *********************************************
     * ************** Assort Function **************
     * *********************************************
     */

    /**
     * Add data to be assorted
     *
     * Assort data and make distribution statistics
     * 
     * @example
     * Input: $data = [
     *      [$key1 => $value1, $key2 => $value2],
     *      [$key1 => $value1, $key2 => $value5],
     * ];
     * Structure: $mappingTable = [
     *      $key1 => [
     *              $value1 => 2,
     *          ],
     *      $key2 => [
     *              $value2 => 1,
     *              $value5 => 1,
     *          ],
     * ];
     * 
     * @param array $mappingTable Save to this argument. Structure: $mappingTable[$key][$value] = $count;
     * @param array $data Data to be assorted
     * @param array $keys Data keys to be assorted
     * @param bool $autoScale Is run auto scale
     * @return StatisticsHelper
     */
    public static function assortAdd(array &$mappingTable, array $data, array $keys = [], bool $autoScale = false)
    {
        // Key filter
        if (count($keys)) {
            $data = array_intersect_key($data, array_flip($keys));
        }

        foreach ($data as $key => $value) {
            // Auto scale
            if ($autoScale) {
                $value = self::_autoScale($value);
            }

            // Assort & statistics
            $mappingTable[$key][$value] = isset($mappingTable[$key][$value]) ? $mappingTable[$key][$value] + 1 : 1;
        }

        return new static();
    }

    /**
     * Get the keys of assorted data
     *
     * @param array $mappingTable
     * @return array
     */
    public static function assortKeys(array $mappingTable)
    {
        $opt = isset($mappingTable) ? array_keys($mappingTable) : [];
        sort($opt);

        return $opt;
    }

    /**
     * Get the statistics of assorted data
     *
     * @param array $mappingTable
     * @param string $key
     * @return int
     */
    public static function assortCount(array $mappingTable, string $key)
    {
        $opt = isset($mappingTable[$key]) ? array_keys($mappingTable[$key]) : 0;
        sort($opt);

        return $opt;
    }

    /**
     * Get assorted data by index
     *
     * @param array $mappingTable
     * @param array|string $indexTo Content index of the data you want to get
     * @param bool $exception default false
     * @throws \Exception
     * @return array|mixed
     */
    public static function assortMap(array $mappingTable, array $indexTo = [], $exception = false)
    {
        return ArrayHelper::getContent($mappingTable, $indexTo, $exception);
    }


    /**
     * ************************************************
     * ************** Numerical Function **************
     * ************************************************
     */


    /**
     * Rebase array data
     * 
     * @example
     * - $data = [3, 6, 7, 9];
     * - $baseValue = 5;
     * - $output = [-2, 1, 2, 4];
     * 
     * @param array $data Data array, numeric[]
     * @return array
     */
    public static function rebase(array $data, $baseValue = 0)
    {
        // Calculate the percentage
        $opt = [];
        foreach ($data as $key => $value) {
            $opt[$key] = $value - $baseValue;
        }

        return $opt;
    }


    /**
     * Calculate the percentage of array data
     *
     * @param array $data
     * @param int $decimals Decimal places, Default 0
     * @return array
     */
    public static function percentage(array $data, int $decimals = 0)
    {
        // Get sum of array data
        $sum = array_sum($data);

        // Calculate the percentage
        $opt = [];
        foreach ($data as $key => $value) {
            $opt[$key] = $sum !== 0 ? self::fixAccuracy($value / $sum * 100, $decimals) : 0;
        }

        return $opt;
    }


    /**
     * Calculate the stack of array data
     *
     * @example
     * - $input = [1, 2, 3, 4, 5];
     * - $headOutput = [15, 14, 12, 9, 5];  // $type = 'head';
     * - $tailOutput = [1, 3, 6, 10 ,15];   // $type = 'tail';
     * 
     * @param array $data Data array for calculate
     * @param string $type Stack to head/tail
     * @return array
     */
    public static function stack(array $data, string $type = 'head')
    {
        switch ($type) {
            default:
            case 'head':
                $opt = self::stack2Head($data);
                break;
            case 'tail':
                $opt = self::stack2Tail($data);
                break;
        }

        return $opt;
    }


    /**
     * Calculate the stack of array data
     * 
     * @example
     * - $input = [1, 2, 3, 4, 5];
     * - $output = [15, 14, 12, 9, 5];
     *
     * @param array $data Data array for calculate
     * @return array
     */
    public static function stack2Head(array $data)
    {
        $opt = [];

        $base = array_sum($data);
        foreach ($data as $key => $value) {
            $opt[$key] = $base;
            $base -= $value;
        }

        return $opt;
    }


    /**
     * Calculate the stack of array data
     *
     * @example
     * - $input = [1, 2, 3, 4, 5];
     * - $output = [1, 3, 6, 10 ,15];
     * 
     * @param array $data Data array for calculate
     * @return array
     */
    public static function stack2Tail(array $data)
    {
        $opt = [];

        $base = 0;
        foreach ($data as $key => $value) {
            $base += $value;
            $opt[$key] = $base;
        }

        return $opt;
    }


    /**
     * Calculate the average of array data
     *
     * @param array $data
     * @return float
     */
    public static function average(array $data)
    {
        $opt = 0;
        $count = count($data);

        if ($count) {
            $opt = array_sum($data) / $count;
        }

        return $opt;
    }


    /**
     * Calculate the standard deviation of array data
     *
     * @param array $data Data array for calculate
     * @return float
     */
    public static function sd(array $data)
    {
        $sd = 0.0;
        $count = count($data);

        // Calculate SD when array is not empty
        if ($count) {
            // Get average
            $avg = array_sum($data) / $count;

            $fVariance = 0.0;
            foreach ($data as $i) {
                $fVariance += pow($i - $avg, 2);
            }
            $sd = (float) sqrt($fVariance / $count);
        }

        return $sd;
    }


    /**
     * **************************************************
     * ************** Approximate Function **************
     * **************************************************
     */


    /**
     * Approximate the contents of the array - Rounding
     * 
     * Use php-bcmath calculation to solve the accuracy processing problem
     * 
     * @param array|float $data 
     * @param int $decimals Decimal places, Default 0
     */
    public static function fixAccuracy($data, int $decimals = 0)
    {
        if (is_array($data)) {
            // Loop data - Handling approximate calculations
            foreach ($data as $k => $value) {
                $data[$k] = self::fixAccuracy($value, $decimals);
            }
        } elseif (is_numeric($data)) {
            // base raised to the power of exponent.
            $pow = self::$_cache[__FUNCTION__][$decimals] ?? self::$_cache[__FUNCTION__][$decimals] = pow(10, $decimals);
            // Use php-bcmath calculation to solve the accuracy processing problem
            $data = bcdiv(round(bcmul($data, $pow, $decimals)), $pow, $decimals);
            // When there is a decimal, you need to filter out the trailing 0
            if ($decimals !== 0) {
                // Filter out the trailing 0
                $data = preg_replace('/[0]+$/', '', $data);
                // Filter out the decimal point in the tail, can't filter with 0, otherwise 20.00 will become 2
                $data = preg_replace('/[\.]+$/', '', $data);
            }
        }

        return $data;
    }


    /**
     * ************************************************
     * ************** Protected Function **************
     * ************************************************
     */

    /**
     * Do auto scale
     * 
     * self::$_options['autoScale'] will be used when auto scaling is runed
     * 
     * @param float $value
     * @return float
     */
    protected static function _autoScale($value)
    {
        $op = self::$_options['autoScale'];

        switch ($op['method']) {
            default:
            case 'value':
                $opt = self::scaleValue($value, $op['step'], $op['type']);
                break;
            case 'level':
                $opt = self::scaleLevel($value, $op['step'], $op['type']);
                break;
        }

        return $opt;
    }
}
