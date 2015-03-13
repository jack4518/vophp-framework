<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>错误信息</title>
<style>
body{
	margin:0px;
	padding:0px;
	font-size:12px;
}
ul,li{
	list-style:none;
}
.misinformation_main{
	margin:20px;
	border:1px solid #dcdcdc;
}
.misinformation_content{
	margin:10px;
}
.misinformation_title{
	height:40px;
	line-height:40px;
	font-size:18px;
	color:#333333;
	border-bottom:2px solid #0088de;
	font-weight:bold;
	font-family:"微软雅黑";
	margin-top:10px;
}
.misinformation_choose{
	line-height:50px;
}
.misinformation_choose a{
	text-decoration:none;
}
.misinformation_choose a:link,.misinformation_choose a:visited{
	color:#3188cf;
}
.misinformation_choose a:hover,.misinformation_choose a:active{
	color:#FF0000;
}
.misinformation_fieldset fieldset{
	border:#e5e5e5;
	padding:10px;
	margin-bottom:20px;
	background:#feffe7;
}
.misinformation_fieldset fieldset legend{
	color:#0179c5;
	font-size:14px;
	font-weight:bold;
}
#misinformation_border{
	border:1px solid #e5e5e5;
}
#misinformation_border table tr > td:first-child{
	width:80px;
	font-weight:bold;
	color:#575757;
}
#misinformation_border table{
	margin-top:10px;
	margin-left:0px;
	*margin-left:10px;
	_margin-left:10px;
}
#misinformation_border table tr td{
	line-height:20px;
}
/*************
.misinformation_fieldset fieldset span{
	line-height:20px;
	margin:0px;
	display:block;
}
********/
.misinformation_footer{
	color:#858585;
	text-align:center;
}
.stack_table td{
	color:#333;
}
</style>
<script language="javascript">
function toggleCode(obj){
	var nodes = obj.childNodes;
	var ul = '';
	for(var i=0; i<nodes.length; i++){
		if(nodes[i].nodeType == 1 && nodes[i].tagName == 'UL'){
			ul = nodes[i];
			break;
		}
	}
	if(ul.style.display == 'none'){
		ul.style.display = "block";
	}else{
		ul.style.display = "none";
	}
}
</script>
</head>

<body>
	
	<div class="misinformation_main">
		<div class="misinformation_content">
			<div class="misinformation_title">错误类型：<font color="#FF0000"><?php echo $type ?>！</font></div>
			<div class="misinformation_choose">
				您可以选择 [<a href="" style="cursor:pointer;" onclick="window.reload(); return false;">重试</a>] 
				[<a href="" style="cursor:pointer;" onclick="window.history.back(); return false;">返回</a>] 或者 
				[<a href="<?php echo $base_url;?>">回到首页</a>]
			</div>
			<div class="misinformation_fieldset">
				<fieldset id="misinformation_border">
					<legend>错误内容</legend>
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
					  <?php if( isset($file) ){?>
					  <tr>
						<td>所在文件：</td>
						<td style="color:#FF0000;"><?php echo $file; ?></td>
					  </tr>
					  <?php } ?>
					  <?php if( isset($line) ){?>
					  <tr>
						<td width="80">错误行号：</td>
						<td>第 <font color="#FF0000"><?php echo $line; ?></font> 行</td>
					  </tr>
					  <?php } ?>
					  <tr>
						<td valign="top">错误信息：</td>
						<td style="color:#FF0000;"><?php echo $message; ?></td>
					  </tr>
					</table>
				</fieldset>
			</div>
			<div class="misinformation_fieldset">
				<fieldset id="misinformation_border">
					<legend>错误跟踪</legend>
					<table width="100%" border="0" cellspacing="0" cellpadding="0" class="stack_table">
					  <tr>
						<td valign="top">错误堆栈：</td>
						<td>
							<?php
								if(is_array($array) && isset($array['error'])){
							?>
									<li onclick="toggleCode(this)" style="cursor:pointer;">
										<span style="font-weight:bold;"><?php echo $array['error']['file']; ?></span>
										<ul>
											<?php foreach($array['error']['source'] as $le => $line_code){ ?>
												<?php if($le == $array['error']['line']){ ?>
												<li style="background:red; color:#fff;">
												<?php }else{?>
												<li>
												<?php }?>
												<span style="color:#666;"><?php echo $le; ?></span>|<span style="padding-left:10px;"><?php echo $line_code; ?></span>
											</li>
											<?php } ?>
										</ul>
									</li>
							<?php
								}
								if(isset($array['stacks'])){
									$is_caller = false;
									foreach($array['stacks'] as $k => $file){
							?>
									<li onclick="toggleCode(this)" style="cursor:pointer;">
										<?php echo $file['file']; ?>
										<?php 
											if(!$file['is_caller'] && $is_caller == false){ 
												$is_caller = true;
										?>
										<ul>
										<?php }else{?>
										<ul style="display:none;">
										<?php }?>
											<?php foreach($file['source'] as $le => $line_code){ ?>
												<?php if($le == $file['line']){ ?>
												<li style="background:green; color:#fff;">
												<?php }else{?>
												<li>
												<?php }?>
												<?php echo $le; ?>|<span style="padding-left:10px;"><?php echo $line_code; ?></span>
											</li>
											<?php } ?>
										</ul>
									</li>
							<?php
									}
								}
							 ?>
						</td>
					  </tr>
					</table>
				</fieldset>
			</div>
		</div>
	</div>
	<div style="text-align: center; padding:0 0 20px 0; color:#666;"><?php echo $array['time']; ?> <?php echo $array['server']; ?> 
	PHP/<?php echo $array['phpver']; ?> <?php echo $array['from']; ?>-><?php echo $array['addr']; ?>(<?php echo $array['host']; ?>) 
	执行时间:<?php echo $array['exectime'] ?>秒 内存开销:<?php echo $array['umem']; ?> Byte 内存峰值:<?php echo $array['mmem']; ?> Byte </div>
	<div class="misinformation_footer">VOPHP开发框架　 Version 1.0  2010 </div>

</body>
</html>
