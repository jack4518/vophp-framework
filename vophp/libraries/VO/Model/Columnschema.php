<?php
/**
 * 定义VO_Model_Conder SQL查询条件处理器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen<cqq254@163.com>
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/

class VO_Model_ColumnSchema
{
    /**
     * 字段名
     * @access private
     * @var string
     */
    public $name;

    /**
     * 字段长度
     * @access private
     * @var string
     */
    public $size;

    /**
     * 字段类型
     * @access private
     * @var int
     */
    public $db_type;

    /**
     * 字段类型归类
     * @access private
     * @var int
     */
    public $type;

    /**
     * 默认值
     * @access private
     * @var mixed
     */
    public $default;

    /**
     * 是否允许为空
     * @access private
     * @var int
     */
    public $allowNull;

    /**
     * 是否为主键
     * @access public
     * @var bool
     */
    public $isPrimaryKey=false;

    /**
     * 是否有外键
     * @access public
     * @var bool
     */
    public $isForeignKey=false;

    /**
     * 是否自增
     * @access public
     * @var bool
     */
    public $autoIncrement=false;

    /**
     * 是否更新字段
     * @access public
     * @var bool
     */
    public $onUpdate    = false;

    /**
     * 精度
     * @access public
     * @var int
     */
    public $precision;

    /**
     * 未知
     * @access public
     * @var int
     */
    public $scale;

    /**
     * 字段注释
     * @access public
     * @var strint
     */
    public $comment='';

    /**
     * 是否存储Data表标记
     * @access public
     * @var bool
     */
    public $isData = false;

    /**
     * 未知字段类型
     * @var string
     */
    const DB_TYPE_UNKNOW   = 'unknow';

    /**
     * 时间类型
     * @var string
     */
    const DB_TYPE_TIME     = 'time';

    /**
     * 字符串类型
     * @var string
     */
    const DB_TYPE_STRING   = 'string';

    /**
     * 整数类型
     * @var string
     */
    const DB_TYPE_INTEGER  = 'integer';

    /**
     * 浮点数类型
     * @var string
     */
    const DB_TYPE_DOUBLE   = 'double';

    /**
     * 布尔类型
     * @var string
     */
    const DB_TYPE_BOOL     = 'boolean';

    /**
     * 初始化
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int     $db_type  字段数据类型
     * @param  string  $default_value  默认值
     * @return void
     */
    public function init($db_type, $default_value)
    {
        $this->db_type = $db_type;
        $this->extractType($db_type);
        $this->extractLimit($db_type);
        if(null !== $default_value){
            $this->extractDefault($default_value);
		}elseif($this->isInteger()){
            $this->default = 0;
		}elseif($this->isString()){
			$this->default = '';
		}
    }

    /**
     * 提取字段类型
     * @access protected
     * @author Chen QiQing <cqq254@163.com>
     * @param  int    $db_type  字段数据类型
     * @return void
     */
    protected function extractType($db_type)
    {
        if(strncmp($db_type,'enum',4)===0){
            $this->type = self::DB_TYPE_STRING;
		}elseif(strpos($db_type,'float')!==false || strpos($db_type,'double')!==false || strpos($db_type, 'decimal')!==false){
            $this->type = self::DB_TYPE_DOUBLE;
		}elseif(strpos($db_type,'bool')!==false){
            $this->type = self::DB_TYPE_BOOL;
		}elseif(preg_match('/(bit|tinyint|smallint|mediumint|int|bigint)/',$db_type)){
            $this->type = self::DB_TYPE_INTEGER;
		}elseif(preg_match('/(char|varchar|tinytext|mediumtext|text|longtext)/',$db_type)){
            $this->type = self::DB_TYPE_STRING;
		}elseif(preg_match('/(date|time|year|datetime|timestamp)/',$db_type)){
            $this->type = self::DB_TYPE_TIME;
		}else{
			$this->type = self::DB_TYPE_UNKNOW;
		}
    }

    /**
     * 提取默认值
     * @access protected
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $default_value
     * @return void
     */
    protected function extractDefault($default_value)
    {
        if($this->db_type==='timestamp' && $default_value==='CURRENT_TIMESTAMP'){
            $this->default = null;
		}else{
			$this->default = $this->convertType($default_value);
		}
    }

    /**
     * 提取分页
     * @access protected
     * @author Chen QiQing <cqq254@163.com>
     * @param  int    $db_type 数据类型
     * @return void
     */
    protected function extractLimit($db_type)
    {
        if (strncmp($db_type, 'enum', 4) === 0){
			preg_match('/\((.*)\)/', $db_type, $matches);
            $values = explode(',', $matches[1]);
            $size = 0;
            foreach($values as $value){
				$n = strlen($value);
                if($n > $size){
					$size=$n;
				}
            }
            $this->size = $this->precision = $size-2;
        }elseif(strpos($db_type,'(') && preg_match('/\((.*)\)/',$db_type,$matches)){
            $values = explode(',',$matches[1]);
            $this->size = $this->precision = (int)$values[0];
            if(isset($values[1])){
				$this->scale=(int)$values[1];
			}
        }
    }

    /**
     * 类型转换
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  value  $value 
     * @returns mixed
     */
    public function convertType($value)
    {
        if(gettype($value)===$this->type || $value===null){
			return $value;
		}
        if($value==='' && $this->allowNull){
			return $this->type==='string' ? '' : null;
		}
        switch($this->type){
			case 'string':
				return (string)$value;
			case 'integer':
				return (integer)$value;
			case 'boolean':
				return (boolean)$value;
			case 'double':
			default:
				return $value;
        }
    }

    /**
     * 返回字段类型名称
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 判断是否是整数类型
     * tinyint smallint mediumint int bigint
	 *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return string
     */
    public function isInteger()
    {
        return self::DB_TYPE_INTEGER == $this->type;
    }

    /**
     * 判断是否为字符串类型
     * char varchar tinytext mediumtext text longtext
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return string
     */
    public function isString()
    {
        return self::DB_TYPE_STRING == $this->type;
    }

    /**
     * 判断是否为浮点数类型
     * float double decimal
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return string
     */
    public function isFloat()
    {
        return self::DB_TYPE_DOUBLE == $this->type;
    }

    /**
     * 判断是否为日期时间类型
     * date time year datetime timestamp
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public function isTime()
    {
        return false;
    }

    /**
     * 判断是否为布尔值类型
     * boolean
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public function isBoolean()
    {
        return false;
    }

    /**
     * 判断是否索引字段
     * MySQL
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public function isIndexColumn()
    {
        return !$this->isData;
    }

    /**
     * 判断是否为Data表字段
     * CouchBase
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public function isDataColumn()
    {
        return $this->isData;
    }

	/**
     * 以数组的形式返回字段信息
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public function toArray(){
	    return array(
			'name' => $this->name,
			'size' => $this->size,
			'db_type' => $this->db_type,
			'type' => $this->type,
			'default' => $this->default,
			'allowNull' =>$this->allowsNull,
			'isPrimaryKey' => $this->isPrimaryKey,
			'isForeignKey' => $this->isForeignKey,
			'autoIncrement' => $this->autoIncrement,
			'onUpdate' => $this->onUpdate,
			'precision' => $this->precision,
			'scale' => $this->scale,
			'comment' => $this->comment,
			'isData' => $this->isData,
	    );
    }
}
