<?php
/**
 * 基于左右值排序的无限节点算法
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-19
 **/
 require_once(VO_LIB_DIR .  DS . 'Model.php');
class VO_Table_Node extends VO_Model{
	/**
	 * 所有子节点,不包含自己
	 * @var string
	 */
	const ALL_CHILDREN_NOTSELF = 'ALL_CHILDREN_NOTSELF';
	
	/**
	 * 包含自己的所有子节点
	 * @var string
	 */
	const ALL_CHILDREN_CONTAINS_SELF = 'ALL_CHILDREN_CONTAINS_SELF';
	
	/**
	 * 不包含自己所有父节点
	 * @var string
	 */
	const ALL_PARENT_NOTSELF = 'ALL_PARENT_NOTSELF';
	
	/**
	 * 包含自己所有父节点
	 * @var string
	 */
	const ALL_PARENT_CONTAINS_SELF = 'ALL_PARENT_CONTAINS_SELF';
	
	/**
	 * 数据库表
	 * @var string
	 */
	protected $_name = null;
	
	/**
	 * 表主键
	 * @var string
	 */
	protected $_key = 'id';
	
	/**
	 * 左值字段名称
	 * @var string
	 */
	protected $_lft = 'lft';
	
	/**
	 * 右值字段名称
	 * @var string
	 */
	protected $_rgt = 'rgt';
	
	
	/**
	 * 获取单一实例
	 * @return VO_Table_Node
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Table_Node ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 设置需要操作的数据表
	 * @param string $table
	 */
	public function setTable($table = null){
		if( !$table ){
			return false;
		}
		$this->_name = $table;
	}
	
	/**
	 * 增加新的节点
	 * @param  $table 	节点表
	 * @param  $parentId 父节点的ID
	 * @param  $param 	要入库的其它信息，以数组的形式呈现，key为字段名，value为值
	 * @return boolean
	 */
	public function addNode($parentId=1, $param=null){
		$fields = "";
		$values = "";
		if( is_array($param) ){
			foreach($param as $field => $value){
				$fields .= "," . $field;
				$values .= ",'" . $value . "'";
			}
		}
		$result = $this->checkNode($parentId);
		if( !$result ){
			$lft = 0;
			$rgt = 1;
		}else{
			//取得父节点的左值,右值
			$lft = $result[$this->_lft];
			$rgt = $result[$this->_rgt];
		}
		
		$this->getDao()->getMasterConnect()->startTransaction();
		
		$sql = "UPDATE `" . $this->getTableName($this->_name) . "` SET " . $this->_lft ."=" . $this->_lft ."+2 WHERE " . $this->_lft .">" . $rgt;
		$update_lft_ret = $this->getDao()->getMasterConnect()->query($sql);

		$sql = "UPDATE `" . $this->getTableName($this->_name) . "` SET " . $this->_rgt ."=" . $this->_rgt ."+2 WHERE " . $this->_rgt .">=" . $rgt;
		$update_rgt_ret = $this->getDao()->getMasterConnect()->query($sql);
		
		$sql = 'INSERT INTO `' . $this->getTableName($this->_name) . '`(' . $this->_lft .',' . $this->_rgt . $fields . ')VALUES(' . $rgt . ',' . ($rgt+1) . $values . ')';
		$insert_ret = $this->getDao()->getMasterConnect()->query($sql);
		$insert_id =  $this->getDao()->getMasterConnect()->getInsertId();

		if($insert_ret && $update_lft_ret && $update_rgt_ret){
			$this->commit();
			return $insert_id;
		}else{
			$this->rollback();
			return false;
		}
	}

	/**
	 * 更新节点
	 * @param  $id			要操作的记录ID
	 * @param  $targetId	目标节点的ID
	 * @param  $param		更新节点的参数
	 */
	public function updateNode($id, $param=null){
      $values = "";
      if( is_array($param) ){
        foreach($param as $k => $v){
            $values .= "," . $k . "='" . $v . "'"; 
        }
        $values = substr($values, 1, strlen($values));
      }
      
      if( !empty($values) ){
	      $sql = "UPDATE `" . $this->getTableName($this->_name) . "` SET " . $values . " WHERE " . $this->_key ."=" . $id;
	      if( $this->getDao()->getMasterConnect()->query($sql) ){
	          return true;
	      }else{
	          return false;
	      }
      }
  }
  
	/**
	 * 删除节点
	 * @param int $cate_id
	 * @return string
	 */
	public function deleteNode($cate_id){
		//取得被删除节点的左右值,检测是否有子节点,如果有就一起删除
		$result = $this->checkNode($cate_id);
		$lft = $result[$this->_lft];
		$rgt = $result[$this->_rgt];
		$this->getDao()->getMasterConnect()->startTransaction();
		$sql = "DELETE FROM `" . $this->getTableName($this->_name) . "` WHERE " . $this->_lft .">=" . $lft . " AND " . $this->_rgt . "<=" . $rgt;
		$delete_ret = $this->getDao()->getMasterConnect()->query($sql);
		
		$val = $rgt - $lft + 1;
		//更新左右值
		$sql = "UPDATE `" . $this->getTableName($this->_name) . "` SET " . $this->_lft ."=" . $this->_lft ."-" . $val . " WHERE " . $this->_lft .">" . $lft;
		$update_left_ret = $this->getDao()->getMasterConnect()->query($sql);
		        
		$sql = "UPDATE `" . $this->getTableName($this->_name) . "` SET " . $this->_rgt . "=" . $this->_rgt . "-" . $val . " WHERE " . $this->_rgt . ">" . $rgt;
		$update_right_ret = $this->getDao()->getMasterConnect()->query($sql);
		if($delete_ret && $update_left_ret && $update_right_ret){
			$this->getDao()->getMasterConnect()->commit();
			return true;
		}else{
			$this->rollback();
			return false;
		}
	} 

	/**
	 * 获取节点信息:
				 1,所有子节点,不包含自己
				 2,包含自己的所有子节点
				 3,不包含自己所有父节点
				 4,包含自己所有父节点
	 * @param $cate_id
	 * @param $type
	 */
	public function getNode($cate_id, $type=self::ALL_CHILDREN_NOTSELF){
		$result = $this->checkNode($cate_id);
		$lft = $result[$this->_lft];
		$rgt = $result[$this->_rgt];
		$sql = 'SELECT * FROM `' . $this->getTableName($this->_name) . '` WHERE ';
		switch ($type) {
			case self::ALL_CHILDREN_NOTSELF :
					$cond = $this->_lft . '>' . $lft . ' AND ' . $this->_rgt . '<' . $rgt; break;
			case self::ALL_CHILDREN_CONTAINS_SELF : 
					$cond = $this->_lft . '>=' . $lft . ' AND ' . $this->_rgt . '<=' . $rgt; break;
			case self::ALL_PARENT_NOTSELF :
					$cond = $this->_lft . '<' . $lft . ' AND ' . $this->_rgt . '>' . $rgt; break; 
			case self::ALL_PARENT_CONTAINS_SELF :
					$cond = $this->_lft . '<=' . $lft . ' AND ' . $this->_rgt . '>=' .$rgt; break;
			default : 
					$cond = $this->_lft . '>' . $lft . ' AND ' . $this->_rgt . '<' . $rgt;
		} 
		$sql .= $cond . ' ORDER BY ' . $this->_lft . ' ASC';
		$rows = $this->getDao()->getMasterConnect()->fetchAll($sql);
		return $rows;
	}
  
	/**
	 * 只获取当前节点下的直属子节点，不包括下级节点
	 * @param $cate_id 节点的ID
	 */
	public function getFristNode($nodeId){
		$arr = array();
		$rgt = 0;
		$rows = $this->getNode($nodeId);
		if($rows){
			foreach($rows as $k => $row){
				$value = $rgt+1;
				if( ($row[$this->_lft]<>$value) && ($k <>0) ){
					continue;
				}
				if($k == 0 || ($value==$row[$this->_lft]) ){
					$arr[] = $row;
				}
				$rgt = $row[$this->_rgt];
			}
		}
		return $arr;
	}

	/**
	 * 取得直属父节点
	 * @param int $cate_id  节点的ID
	 */
	function getParent($cate_id){
		$rows = $this->getNode($cate_id, self::ALL_PARENT_NOTSELF);
		$parent = @$rows[count($rows)-1]>0 ? $rows[count($rows)-1] : 0;
		return $parent;
	}

	/**
	 * 取得从根节点到当前节点的节点路径
	 * @param int $cate_id  节点的ID
	 */
	public function getPath($nodeId, $containsRoot=true){
		$rows = $this->getNode($nodeId, self::ALL_PARENT_CONTAINS_SELF);
		if($containsRoot === false){
			array_shift($rows);
		}
		return $rows;
	}

	/**
	 * 获取一个节点列表，并且space字段返回节点所在层级组合的某字符(用于在页面上实现层级缩进输出)
	 * @param $space   		节点前的分隔符，默认为空格
	 * @param $contairRoot  返回的数据是否包含根节点
	 */
	public function getAllNodeList($space='　', $contairRoot=false, $where='', $limit=20000, $offset=0){
		if($contairRoot==true){
			$seek = 1;
		}else{
			$seek = 2;
		}
		$sql = "SELECT CONCAT( REPEAT( '" . $space . "', (COUNT(*) - " . $seek . ") )) AS space,node.*, (COUNT(*) - 1) AS depth
				FROM `" . $this->getTableName($this->_name) . "` AS node,`" . $this->getTableName($this->_name) . "` AS parent
				WHERE node." . $this->_lft . " BETWEEN parent." . $this->_lft . " 
				AND parent." . $this->_rgt . "
				GROUP BY node." . $this->_key . " 
				ORDER BY node." . $this->_lft . ' 
				LIMIT ' . $limit . ' OFFSET ' . $offset;
		$rows = $this->getDao()->getMasterConnect()->fetchAll($sql);
		if($contairRoot === false){
			array_shift($rows);
		}
		return $rows;   
	}
	
	/**
	 * 获取不包含自己的子孙节点
	 * @param int $nodeId
	 */
	public function getChildNotSelf($nodeId,  $space='　',  $containsRoot=true, $where='', $limit=20000, $offset=0){
		 return $this->getNodeList($nodeId, $space, self::ALL_CHILDREN_NOTSELF, $containsRoot);
	}
	
	/**
	 * 获取包含自己的子孙节点
	 * @param int $nodeId
	 */
	public function getChildAndSelf($nodeId,  $space='　',  $containsRoot=true){
		 return $this->getNodeList($nodeId, $space, self::ALL_CHILDREN_CONTAINS_SELF, $containsRoot, $where='', $limit=20000, $offset=0);
	}

	/**
	 * 获取包含自己的祖先节点
	 * @param int $nodeId
	 */
	public function getParentAndSelf($nodeId,  $space='　',  $containsRoot=true){
		 return $this->getNodeList($nodeId, $space, self::ALL_PARENT_CONTAINS_SELF, $containsRoot, $where='', $limit=20000, $offset=0);
	}
	

	/**
	 * 获取不包含自己的祖先节点
	 * @param int $nodeId
	 */
	public function getParentNotSelf($nodeId,  $space='　',  $containsRoot=true){
		 return $this->getNodeList($nodeId, $space, self::ALL_PARENT_NOTSELF, $containsRoot, $where='', $limit=20000, $offset=0);
	}		

	/**
	 * 获取一个节点列表，并且space字段返回节点所在层级组合的某字符(用于在页面上实现层级缩进输出),
	 * @param $cate_id  当前节点的ID
	 * @param $space  节点前的分隔符，默认为空格
	 * @param $type  返回的类型：
								1:不包含自己的所有子节点;
								2:包含自己的所有子节点;
								3:不包含自己所有父节点
								4:包含自己所有父节点
	 * @param $contairRoot  返回的数据是否包含根节点
	 */
	private function getNodeList($cate_id=1, $space='　', $type=self::ALL_CHILDREN_NOTSELF, $containsRoot=true, $where='', $limit=20000, $offset=0){
		if($containsRoot==true){
			$seek = 1;
		}else{
			$seek = 2;
		}
		$result = $this->checkNode($cate_id);
		$lft = $result[$this->_lft];
		$rgt = $result[$this->_rgt];
		$sql = "SELECT CONCAT( REPEAT( '" . $space . "', (COUNT(*) - " . $seek . ") )) AS space,node.*, (COUNT(*) - 1) AS depth
					FROM `" . $this->getTableName($this->_name) . "` AS node,`" . $this->getTableName($this->_name) . "` AS parent";
		switch ($type) {
			case self::ALL_CHILDREN_NOTSELF :
					$cond = 'node.' . $this->_lft . '>' . $lft . ' AND node.' . $this->_lft . '<' . $rgt; break;
			case self::ALL_CHILDREN_CONTAINS_SELF : 
					$cond = 'node.' . $this->_lft . '>=' . $lft . ' AND node.' . $this->_lft . '<=' . $rgt; break;
			case self::ALL_PARENT_NOTSELF :
					$cond = 'node.' . $this->_lft . '<' . $lft . ' AND node.' . $this->_rgt . '>' . $rgt; break; 
			case self::ALL_PARENT_CONTAINS_SELF :
					$cond = 'node.' . $this->_lft . '<=' . $lft . ' AND node.' . $this->_rgt . '>=' . $rgt; break;
			default:
					$cond = 'node.' . $this->_lft . '>' . $lft . ' AND node.' . $this->_lft . '<' . $rgt;
		}
		$sql .= ' WHERE node.' . $this->_lft . ' BETWEEN parent.' . $this->_lft . ' 
				  AND parent.' . $this->_rgt . ' 
				  AND ' . $cond . ' 
				  GROUP BY node.' . $this->_key . ' 
				  ORDER BY node.' . $this->_lft . ' 
				  LIMIT ' . $limit . ' OFFSET ' . $offset;
		$rows = $this->getDao()->getMasterConnect()->fetchAll($sql);
		if($containsRoot === false){
			array_shift($rows);
		}
		return $rows;
	}
 
	/**
	 * 获取一个节点列表，并且这个列表不包含$nodeId节点及其子孙节点
	   $space字段返回节点所在层级组合的某字符(用于在页面上实现层级缩进输出),
	   (一般常用于显示在编辑节点时，在编辑其父分类的时候，因为不能让自己移动到自己的子孙节点上)
	 * @param $nodeId  当前节点的ID
	 * @param $space    节点前的分隔符，默认为空格
	 * @param $containsRoot  返回的数据是否包含根节点
	 */
	function getUnContainsMyChildNode($nodeId=1, $space="　", $containsRoot=false){
		$all 	 = $this->getNodeList(1, $space, self::ALL_CHILDREN_CONTAINS_SELF, true);//得到所有节点
		$mychild = $this->getNodeList($nodeId, $space, self::ALL_CHILDREN_CONTAINS_SELF, true);//得到包含自己及子孙的节点
		$i = 0;
		//去除自己及自己的子孙节点
		foreach($all as $k => $v){
			$i++;
			foreach($mychild as $key => $val){
				if($v[$this->_key] == $val[$this->_key]){
					$i--;
					array_splice($all,$i,1);
				}
			}
		}
		if($containsRoot === false){
			array_shift($all);
		}
		return $all;
	}

	/**
	 * 移动节点,如果节点有子节点也一并移动
	 * @param $Selfcatid    源节点的ID
	 * @param $Parentcatid  目标父节点
	 */
	public function moveNode($nodeId,$targetId){
	    $selfNode = $this->checkNode($nodeId);
	    $targetNode = $this->checkNode($targetId);
	
	
	    $selfLft = $selfNode[$this->_lft];
	    $selfRgt = $selfNode[$this->_rgt];
	    $value = $selfRgt - $selfLft;
	    //取得所有节点的ID方便更新左右值
	    $Nodes = $this->getNode($nodeId,self::ALL_CHILDREN_CONTAINS_SELF);
	    foreach($Nodes as $v){
	        $ids[] = $v[$this->_key];
	    }
	    $inIds = implode(",", $ids);
	
	    $targetLft = $targetNode[$this->_lft];
	    $targetRgt = $targetNode[$this->_rgt];
	    if( $targetRgt > $selfRgt ){
	        $UpdateLeftSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET $this->_lft=$this->_lft-$value-1 WHERE $this->_lft>$selfRgt AND $this->_rgt<=$targetRgt";
	        $UpdateRightSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET $this->_rgt=$this->_rgt-$value-1 WHERE $this->_rgt>$selfRgt AND $this->_rgt<$targetRgt";
	        $TmpValue=$targetRgt - $selfRgt-1;
	        $UpdateSelfSQL = "UPDATE `" . $this->getTableName($this->_name)."` SET $this->_lft=$this->_lft+$TmpValue,$this->_rgt=$this->_rgt+$TmpValue WHERE `$this->_key` IN($inIds)";
	    }else{
	        $UpdateLeftSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET $this->_lft=$this->_lft+$value+1 WHERE $this->_lft>$targetRgt AND $this->_lft<$selfLft";
	        $UpdateRightSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET $this->_rgt=$this->_rgt+$value+1 WHERE $this->_rgt>=$targetRgt AND $this->_rgt<$selfLft";
	        $TmpValue=$selfLft - $targetRgt;
	        $UpdateSelfSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET $this->_lft=$this->_lft-$TmpValue,$this->_rgt=$this->_rgt-$TmpValue WHERE `$this->_key` IN($inIds)";
	    }
	    $this->getDao()->getMasterConnect()->startTransaction();
	    $q1 = $this->getDao()->getMasterConnect()->query($UpdateLeftSQL);
	    $q2 = $this->getDao()->getMasterConnect()->query($UpdateRightSQL);
	    $q3 = $this->getDao()->getMasterConnect()->query($UpdateSelfSQL);
	    if($q1 && $q2 && $q3){
	    	$this->getDao()->getMasterConnect()->commit();
	    }else{
	    	$this->getDao()->getMasterConnect()->rollback();
	    }
	    return true;
	  }
	/*
	function moveNode($id,$targetId){
		$self = $this->checkNode($id);
		$target = $this->checkNode($targetId);
		
		$selfLft = $self[$this->_lft];
		$selfRgt = $self[$this->_rgt];
		$targetLft = $target[$this->_lft];
		$targetRgt = $target[$this->_rgt];
				
		$value = $selfRgt - $selfLft;
		
		//取得所有节点的ID方便更新左右值
		$nodes = $this->getNode($id, self::ALL_CHILDREN_CONTAINS_SELF);
		foreach($nodes as $v){
			$ids[] = $v[$this->_key];
		}
		$inIds = implode(',', $ids);
		if( $targetRgt > $selfRgt ){
			$UpdateLeftSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET lft=lft-" . ($value-1) . " WHERE lft>" . $selfRgt . " AND rgt<=" . $targetRgt;
			$UpdateRightSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET rgt=rgt-" . ($value-1) . " WHERE rgt>" . $selfRgt . " AND rgt<" . $targetRgt;
			$TmpValue = $targetRgt-$selfRgt-1;
			$UpdateSelfSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET lft=lft+" . $TmpValue . ",rgt=rgt+" . $TmpValue . " WHERE `id` IN(" . $inIds . ")";
		}else{
			$UpdateLeftSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET lft=lft+" . ($value+1) . " WHERE lft>" . $targetRgt . " AND lft<" . $selfLft;
			$UpdateRightSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET rgt=rgt+" . ($value+1) . " WHERE rgt>=" . $targetRgt . " AND rgt<" .$selfLft;
			$TmpValue = $selfLft-$targetRgt;
			$UpdateSelfSQL = "UPDATE `" . $this->getTableName($this->_name) . "` SET lft=lft-" . $TmpValue . ",rgt=rgt-" . $TmpValue . " WHERE `id` IN(" . $inIds . ")";
		}
		echo $UpdateSelfSQL;
		$this->getDao()->getMasterConnect()->query($UpdateLeftSQL);
		$this->getDao()->getMasterConnect()->query($UpdateRightSQL);
		$this->getDao()->getMasterConnect()->query($UpdateSelfSQL);
		return true;
	}
	*/

	/**
	 * 检测节点是否存在
	 * @param $nodeId  节点的ID
	 */
	public function checkNode($nodeId){
		//检测父节点ID是否存在
		$sql="SELECT * FROM `" . $this->getTableName($this->_name) . "` WHERE " . $this->_key ."='" . $nodeId . "' LIMIT 1";
		$result=$this->getDao()->getMasterConnect()->fetchRow($sql);
		if(count($result)<1){
			return false;
		}
		return $result;     
	}

	/**
	 * 将节点树转换成数组
	 * @param $cate_id  节点的ID
	 */
	public function nodeToArray($cate_id=0){
		$output = array();
		if( $cate_id==0 ){
			$cate_id = $this->getRootId();
		}
		if( empty($cate_id) ){
			return array();
			exit;
		}
		$sql = 'SELECT lft, rgt FROM `' . $this->getTableName($this->_name) . '` WHERE ' . $this->_key . '=' . $cate_id; 
		$row = $this->getDao()->getMasterConnect()->fetchRow($sql);
		if($row) {
			$right = array(); 
			$sql = 'SELECT * FROM `' . $this->getTableName($this->_name) . '` WHERE ' . $this->_lft . ' BETWEEN ' . $row[$this->_lft] . ' AND ' . $row[$this->_rgt] . ' ORDER BY ' . $this->_lft . ' ASC';
			$rows = $this->getDao()->getMasterConnect()->fetchAll($sql);
			foreach($rows as $row){ 
				$parent = $this->getParent($row[$this->_key]);
				$row['parent'] = $parent[$this->_key];
				$row['isLeaf'] = $this->isLeafNode($row[$this->_key]);
				if (count($right)>0){ 
					while ($right[count($right)-1]<$row[$this->_rgt]) { 
						array_pop($right);
					} 
				}
				$deep = count($right);		  
				$output[] = array('sort'=>$row,'depth'=>$deep);
				$right[] = $row[$this->_rgt];
			}
		}
		return $output;     
	}
  
	/**
	 * 获取节点的根目录
	 */
	public function getRootId(){
		$sql = 'SELECT * FROM`' . $this->getTableName($this->_name) . '` ORDER BY ' . $this->_lft .' ASC LIMIT 1';
		$row = $this->getDao()->getMasterConnect()->fetchRow($sql);
		if(count($row)>0){
			return $row[$this->_key];
		}else{
			return false;
		}
	}
  
	/**
	 * 判断指定节点是否为叶子节点
	 * @param $nodeId  节点的ID
	 */
	public function isLeafNode($nodeId){
		$sql = 'SELECT ' . $this->_lft . ',' . $this->_rgt . ' FROM`' . $this->getTableName($this->_name) . '` WHERE ' . $this->_key . '=' . $nodeId;
		$row = $this->getDao()->getMasterConnect()->fetchRow($sql);
		if( ($row[$this->_lft]+1) == $row[$this->_rgt] ){
			return true;
		}else{
			return false;
		}
	}
  
	/**
	 * 以多维数组的形式返回所有的节点(有几级就有几维数组)
	 * @param int $cate_id  节点的ID
	 * @param string $field 要取得的字段名
	 */
	public function getAll($nodeId){
		$arr = array();
		$rows = $this->getFristNode($nodeId);
		foreach($rows as $k => $row){
			$nodeId = $row[$this->_key];
			if($row){
					foreach($row as $field => $value){
						$arr[$field] = $row[$field];
					}
			}
			if(!$this->isLeafNode($nodeId)){
				$arr['nodeid'] = $this->_getChild($nodeId, $field);
			}
		}
		return $arr;
	}

	/**
	 * 获取某一节点的子节点
	 * @param int $nodeId     节点的ID
	 * @param string $field    要取得的字段名
	 */
	private function _getChild($nodeId){
		$newarr = array();
		$rows = $this->getFristNode($nodeId);
		foreach($rows as $k => $row){
			$nodeId = $row[$this->_key];
			if($row){
				foreach($row as $field => $value){
					$newarr[$field] = $row[$field];
				}
			}
			if(!$this->isLeafNode($nodeId)){
				$newarr['nodeid'] = $this->_getChild($nodeId, $field);
			}
		}
		return $newarr;
	}
}