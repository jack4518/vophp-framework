<?php
/**
 * 定义 VO_Html_Form HTML页面表单类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-11-12
 **/
defined('VOPHP') or die('Restricted access');

include_once VO_LIB_DIR . DS . 'Html' . DS . 'Tag.php';
class VO_Html_Form extends VO_Html_Tag{
	
	/**
	 * 生成表单头
	 * @param	array	$attributes		表单的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function start(array $attributes = array()){
		$defaultOption = array(
			'name'		=>	'voForm',
			'id'		=>	'voForm',
			'method'	=>	'post',
			'action'	=>	'',
			'target'	=>	'',
			'enctype'	=>	'application/x-www-form-urlencoded'
		);
		
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attributes = array_merge($defaultOption, $attributes);
		
		return self::createTag('form', '', $attributes);
	}
	
	/**
	 * 生成表单尾
	 * @return string
	 */
	public static function end(){
		return self::endTag('form');
	}
	
	/**
	 * 生成隐藏标签<input type='hidden'>
	 * 
	 * @param	string	$name 标签名称
	 * @param	string	$value 标签值
	 * @param	array	$attributes  其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function hidden($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'hidden',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes);		
	}
	
	/**
	 * 生成文本框标签<input type='text'>
	 * 
	 * @param	string	$name 标签名称
	 * @param	string	$value 标签值
	 * @param	array	$attributes  其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function input($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'text',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}
	
	/**
	 * 生成密码框标签<input type='password'>
	 * 
	 * @param	string	$name 标签名称
	 * @param	string	$value 标签值
	 * @param	array	$attributes  其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function password($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'password',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}
	
	/**
	 * 生成多行文本框标签<textarea>多行文本框内容</textarea>
	 * 
	 * @param	string	$name 标签名称
	 * @param	string	$value 标签值
	 * @param	array	$attributes  其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function textarea($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'text',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'cols' 		=>	'90', 
			'rows' 		=>	'12',
		);
		$attributes = array_merge($attr, $attributes);
		$textarea = self::createTag('textarea', $value, $attributes, true);
		$textarea .= self::endTag('textarea');
		return $textarea;	
	}
	
	/**
	 * 生成复选框标签<input type="checkbox" />
	 * 
	 * @param	string	$name		标签名称
	 * @param	string	$value		标签值
	 * @param	bool	$checked	是否选中
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function checkbox($name, $value='', $checked=false, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'checkbox',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		if($checked){
			$attr['checked'] = 'checked';
		}
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);
	}

	/**
	 * 生成单选框标签<input type="radio" />
	 * 
	 * @param	string	$name		标签名称
	 * @param	string	$value		标签值
	 * @param	bool	$checked	是否选中
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function radiobox($name, $value='', $checked=false, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'radio',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		if($checked){
			$attr['checked'] = 'checked';
		}
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);
	}	

	/**
	 * 生成复选列表标签
	 * 		<select name='dropdown' multiple="multiple">
	 * 			<option value='1'>选项1</option>
	 * 			<option value='2'>选项2</option>
	 * 		</select>
	 * 
	 * @param	string	$name		标签名称
	 * @param	array	$options	选项数组
	 * 				$options = array(
	 *		 			'shanghai'	=>	'上海市',
	 *					'guangzhou'	=>	'广州市',
	 *		 			'北京市'	=>	array(
	 *						'haidian'	=>	'海淀区',
	 *						'chaoyang'	=>	'朝阳区',
	 *						'dongcheng'	=>	'东城区'
	 *					),
	 *				);
	 * @param	mixed	$selected	默认选择项,如果有多个选项可以是一个索引数组，值为$options的键值,如：$selected = array( 'haidian', 'dongcheng' );
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function multiSelect($name, $options=array(), $selected=array(), $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'password',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'multiple'	=>	'multiple',
		);
		$attributes = array_merge($attr, $attributes);
		return self::dropDown($name, $options, $selected, $attributes);		
	}

	/**
	 * 生成下拉列表框标签
	 * 		<select name='dropdown'>
	 * 			<option value='1'>选项1</option>
	 * 			<option value='2'>选项2</option>
	 * 		</select>
	 * 
	 * @param	string	$name		标签名称
	 * @param	array	$options	选项数组
	 * 				$options = array(
	 *		 			'shanghai'	=>	'上海市',
	 *					'guangzhou'	=>	'广州市',
	 *		 			'北京市'	=>	array(
	 *						'haidian'	=>	'海淀区',
	 *						'chaoyang'	=>	'朝阳区',
	 *						'dongcheng'	=>	'东城区'
	 *					),
	 *				);
	 * @param	mixed	$selected	默认选择项,如果有多个选项可以是一个索引数组，值为$options的键值,如：$selected = array( 'haidian', 'dongcheng' );
	 * 								当为单选下拉列表框时，如果$selected有多个值，则选择最后一个值做为选中值
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function dropDown( $name, $options=array(), $selected=array(), $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$type = 'select';
		$attr = array(
			'type'		=>	'select',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
		);
		$attr = array_merge($attr, $attributes);
		if( !is_array($selected) ){
			$selected = (array) $selected;
		}
		if( (count($selected) > 1) && (@$attributes['multiple'] != '') ){
			$attr['multiple'] = 'multiple';
		}else{
			//当为单选下拉列表框时，如果$selected有多个值，则选择最后一个值做为选中值
			$selected = (array)$selected[count($selected)-1];
		}
			
		$select = self::createTag($type, '', $attr);
		foreach ($options as $key => $val){
			$key = (string) $key;

			if( is_array($val) ){
				$select .= '<optgroup label="' . $key . '">';
				foreach ($val as $optgroup_key => $optgroup_val){
					$is_selected = (in_array($optgroup_key, $selected)) ? ' selected="selected"' : '';

					$select .= '<option value="' . $optgroup_key . '"' . $is_selected . '>' . (string) $optgroup_val . '</option>';
				}
				$select .= '</optgroup>';
			}else{
				$is_selected = (in_array($key, $selected)) ? ' selected="selected"' : '';
				$select .= '<option value="' . $key . '"' . $is_selected . '>' . (string) $val . '</option>';
			}
		}
		$select .= self::endTag($type);
		
		return $select;	
	}
	
	/**
	 * 生成文件上传标签<input type='file' name='' id=''>
	 * 
	 * @param	string	$name		标签名称
	 * @param	string	$value		标签值
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function file($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'file',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}
	
	/**
	 * 生成图像域标签<input type='image' name='imagebtn' id='imagebtn' src='图片路径' />
	 * 
	 * @param	string	$name		标签名称
	 * @param	string	$src		图像域的路径
	 * @param	string	$value		标签值
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function image($name, $src, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'image',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'src'		=>	$src,
			'value'		=>	$value,
			'class'		=>	'',
			'border'	=>	0,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}	

	/**
	 * 生成label标签:<label for=''>标签内容</label>
	 * 
	 * @param	string	$name 		标签名称
	 * @param	string	$id			标签的id,用于for属性
	 * @param	string	$text 		标签内容
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function label($name, $id='', $text='默认标签', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'name'		=>	$name,
		);
		if( !empty($id) ){
			$attr['for'] = $id;
		}
		$attributes = array_merge($attr, $attributes);
		$label = self::createTag('label', $text, $attributes, true);
		$label .= self::endTag('label');
		return $label;		
	}

	/**
	 * 生成提交按钮<input type='submit' />
	 * 
	 * @param	string	$name		按钮名称
	 * @param	string	$value		按钮的值，即显示的文本
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function submit($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'submit',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}

	/**
	 * 生成重置按钮<input type='reset' />
	 * 
	 * @param	string	$name		按钮名称
	 * @param	string	$value		按钮的值，即显示的文本
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function reset($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'reset',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);		
	}

	/**
	 * 生成普通按钮<input type='button' />
	 * 
	 * @param	string	$name		按钮名称
	 * @param	string	$value		按钮的值，即显示的文本
	 * @param	array	$attributes	其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function button($name, $value='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'button',
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'value'		=>	$value,
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('input', '', $attributes, true);
	}
	
	/**
	 * 生成普通按钮<fieldset><legend>文本块</legend>我是fieldset的内容</fieldset>
	 * 
	 * @param	string	$legendText		按钮名称
	 * @param	string	$text			fieldset的内容
	 * @param	array	$attributes		其它的属性
	 * @return	string	生成后的HTML标签
	 */
	public static function fieldset( $legendText='', $text='', $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		
		$type = 'fieldset';
		
		$fieldset = self::createTag($type, '', $attributes);
		if ($legendText != ''){
			$fieldset .= '<legend>' . $legendText . '</legend>';
		}
		$fieldset .= $text;		
		$fieldset .= self::endTag($type);
		return $fieldset;
	}
}