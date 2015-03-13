<?php
/**
 * 定义  VO_Validator 验证类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-03
 **/

defined('VOPHP') or die('Restricted access');

class VO_Validator{
		
	// 当有规则失败时，跳过余下的规则
    const SKIP_ON_FAILED = 'skip_on_failed';
	// 跳过其他规则
    const SKIP_OTHERS    = 'skip_others';
    // 验证通过
    const PASSED         = true;
    // 验证失败
    const FAILED         = false;
    // 检查所有规则
    const CHECK_ALL      = true;
    
    //验证规则实例
    
    private $validator_rules = null;
	
	/**
	 * 构造器
	 * @return VO_Validator
	 */
	public function __construct(){
		$this->validator_rules = VO_Validator_Rule::getInstance();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Validator
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Validator ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 回调方法是否有效
	 * 
	 * 参数可以为如下几种:
	 * 	1. "class::method" 静态方法调用
	 *  2. array(object $obj,"method_name")
	 *  3. "function_name"
	 * @param mixed $callback
	 * @return Boolean
	 */
	private function isCallback($callback){
		return is_callable($callback);
	}
	
	/**
	 * 将回调函数 名称转储成字符串样式
	 * @param $callback
	 */
	private function callbackToString($callback){
		// function || Class::func
		if (is_string($callback)) return $callback ;
		// & $obj , func
		else if (is_array($callback)){
			return get_class(array_shift($callback)) . '::' . array_shift($callback) ;
		}
		throw new Exception('$callback must a string or array(Object $obj,string $method)');
	}
	
	/**
	 * 用多组规则验证关联数组的值
	 * 
	 * 每个关联数组的元素均对应一组验证规则,如果这组验证规则不存在,则当成此元素无须验证
	 * 只有当关联数组的所有元素都通过其对应的组验证规则时，validateRowsWithRules() 方法才会返回 true。
	 * 
	 *
     * 用法：
     * validateRowsWithRules(
     * 		array(
     * 			'name' => 'iamsese' ,
     * 			'age' => 26
     * 		), 
     * 		array(
     *         'name' => array(
     *         		array('not_empty', '用户名不能为空'),
     *         		array('min_length', 5, '用户名不能少于 5 个字符'),
     *         		array('max_length', 20, '用户名不能超过 20 个字符'),
     *         ) ,
     *         
     *         'age' => array(
     *         		array('not_null', '年龄不能为空'),
     *         		array('is_int', '年龄必须是整数'),
     *         		array('between',18,50,true,'年龄必须是[18,50]的整数'),
     *         ) ,
     *      ),
     *      $flds_failed 
     * );
     * 
     * * 如果提供了 $flds_failed 参数，则验证失败的规则会存储在 $failed 参数中
     * 
	 * @param array $rows 要验证的关联数组
	 * @param array $flds_rules 关联数组的键对应的多组规则
	 * @param mixed $flds_failed 保存验证失败的 字段->错误信息 的关联数组
	 */
	public function validateRowsWithRules(array $rows,array $flds_rules=null, &$flds_failed = null){
		$rules_failed = null;	
		if (!$this->validator_rules->arrayNotEmpty($flds_rules)) return true ;
		if (!$this->validator_rules->arrayNotEmpty($rows)) $rows = array() ;
		
		$flds_failed = array();
		foreach ($flds_rules as $fld => $rules){
			if (!$this->validator_rules->arrayNotEmpty($rules)){
				continue;
			}
			if (!isset($rows[$fld])){
				$rows[$fld] = null;
			}
			$value = $rows[$fld];
			$errors = array(); // $fld -> errorInfo 
			
			foreach ($rules as $index => $rule){
				// $rule => array(validation, validationParams, errorInfo)
				$errors[$this->callbackToString($rule[0])] = array_pop($rules[$index]); //弹出错误信息
			}
			if ($this->validateBatch($value,$rules,false,$rules_failed)){
				continue ;
			}
			$flds_failed[$fld] = $errors[$this->callbackToString(array_pop($rules_failed))] ;
		}
		return empty($flds_failed) ;		
	}
	
	
	/**
     * 用一组规则验证值
     *
     * validateWithRules() 方法对一个值应用一组验证规则，并返回最终的结果。
     * 如果规则不是合法的数组,则当成无须验证而返回true
     * 
     * 这一组验证规则中只要有一个验证失败，都会返回 false。
     * 只有当所有规则都通过时，validateWithRules() 方法才会返回 true。
     *
     * 用法：
     * validateWithRules(
     * 		$value,
     * 		array(
     *         array('is_int', '必须是整数'),
     *         array('between',18,50,false,'必须在[18,50]的之间'),
     * 		),
     * 		$failed
     * );
     *
     * $rules 参数必须是一个数组，包含多个规则，及验证规则需要的参数。
     * 每个规则及参数都是一个单独的数组。
     *
     * 如果提供了 $failed 参数，则验证失败的规则会存储在 $failed 参数中：
     *
     * @param mixed $value 要验证的值
     * @param array $rules 由多个验证规则及参数组成的数组
     * @param mixed $failed 保存验证失败的错误信息的内容
     *
     * @return boolean 验证结果
     */
	public function validateWithRules($value,array $rules=null, & $failed = null){
		$rules_failed = null;
		/* @var $validator Zend_Custom_Validate */
		if (!$this->validator_rules->arrayNotEmpty($rules)) return true ;
		
		$errors = array(); // $fld -> errorInfo 
		foreach ($rules as $index=>$rule)
			// $rule => array(rule, validationParams, errorInfo)
			$errors[$this->callbackToString($rule[0])] = array_pop($rules[$index]); // 弹出错误信息

		if ($this->validator_rules->validateBatch($value,$rules,false,$rules_failed)) return true;
		
		$failed = $errors[$this->callbackToString(array_pop($rules_failed))] ;
		
		return false ;		
	}
	
	/**
     * 用一组规则验证值
     *
     * validateBatch() 方法对一个值应用一组验证规则，并返回最终的结果。
     * 这一组验证规则中只要有一个验证失败，都会返回 false。
     * 只有当所有规则都通过时，validateBatch() 方法才会返回 true。
     *
     * 用法：
     * validateBatch($value, array(
     *         array('is_int'),
     *         array('between', 2, 6),
     * ));
     *
     * $validations 参数必须是一个数组，包含多个规则，及验证规则需要的参数。
     * 每个规则及参数都是一个单独的数组。
     *
     * 如果提供了 $failed 参数，则验证失败的规则会存储在 $failed 参数中：
     *
     * @param mixed $value 要验证的值
     * @param array $validations 由多个验证规则及参数组成的数组
     * @param boolean $check_all 是否检查所有规则
     * @param mixed $failed 保存验证失败的规则名
     *
     * @return boolean 验证结果
     */ 
	public function validateBatch($value, array $validations, $check_all = false, & $failed = null){
		$result = true;
		$failed = array();
		foreach ($validations as $validation){
			$rule = $validation[0]; // eg. is_int
			$validation[0] = $value ; 
			$ret = $this->validateByArgs($rule,$validation) ;
			
			// 跳过余下的验证规则
            if ($ret === self::SKIP_OTHERS)
            {
                return $result;
            }

            if ($ret === self::SKIP_ON_FAILED)
            {
                $check_all = false;
                continue;
            }

            if ($ret) continue;
			
            $failed[] = $rule;
            $result = $result && $ret;

            if (!$result && !$check_all) return false;
		}
		return (bool)$result;
	}
	
	/*
	 * 用单个规则验证值
	 * validate($value, 'max', 5)) <==> validateByArgs('max', array($value, 5));
	 * 
	 * validate($value, 'between', 1, 5) <==> validateByArgs('between', array($value, 1,5));
	 * 
	 * validate($value, 'custom_callback', $args) <==> validateByArgs('custom_callback', array($value, $args));
	 */
	public function validate($value, $validation){
		$args = func_get_args();
		unset($args[1]);
        $result = $this->validateByArgs($validation, $args);
        return (bool)$result;
	}
	
	// validateByArgs() 方法与 validate() 方法功能相同，只是参数格式不同
	public function validateByArgs($validation, array $args){
		//if (empty($validation)) return null ;
		$method = null ;
		if ($this->validator_rules->stringNotEmpty($validation)){
			if (method_exists($this->validator_rules, $validation)){
				$method = array(& $this->validator_rules, $validation);
			}elseif (strpos($validation, '::') && $this->isCallback($validation)){
				$method = explode('::', $validation);
			}elseif ($this->isCallback($validation)){
				$method = $validation ;
			}
		}elseif ($this->validator_rules->arrayNotEmpty($validation) && $this->isCallback($validation)){
			$method = $validation ;
		}
		return $method ? call_user_func_array($method, $args): null;
	}
	
	/**
     * 如果为空（空字符串或者 null），则跳过余下的验证
     * @par
     */
    public function skip_empty($value)
    {
        return (strlen($value) == 0) ? self::SKIP_OTHERS : true;
    }

    /**
     * 如果值为 NULL，则跳过余下的验证
     * @param mixed $value
     * @return Boolean
     */
    public function skip_null($value)
    {
        return (is_null($value)) ? self::SKIP_OTHERS : true;
    }
    

    /**
     * 如果接下来的验证规则出错，则跳过后续的验证
     */
    function skip_on_failed()
    {
        return self::SKIP_ON_FAILED;
    }
	
}