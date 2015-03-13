<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
 <head>
  <title><?php echo $this->_data['title']; ?></title>
  <meta name="Keywords" content="HET,模板引擎,PHP,Hellex">
  <meta name="Description" content="">
  <meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
  <style>
  body {
	margin: 3px;
	background-color: #FFFFFF;
	color: #333333;
	text-align:center;
	}
	body, table, input, select, textarea {
	font: 12px 宋体, 新宋体, Tahoma, Verdana;
	}
  </style>
 </head>
<body>
<div id="whole" style="margin:12px;border:1px solid #F5F5F5;width:780px;">
	<div style="margin:12px;width:760px;">
		 <span style="font-size:22px;color:blue;font-weight:bold;">HET Usage (美工篇)</span><br/><br>
		 (本文件由<a href="demo.php">demo.php</a>的html另存而成)</br>
		 by <span style="color:blue;"><a href="mailto:hellex@live.com">Hellex</a></span>	 
	</div>

   <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">注意事项</span><br/><br/>
		  HET的解释语体中，在单引号中使用单引号，必须将单引号\转义<br/>
		  例如 <br/>
		  {$abc = '$_ABC['abc']'} <span style="color:red">错误</span><br/>
		  {$abc = '$_ABC[\'abc\']'} <span style="color:green">正确</span><br/>
		  {$abc = '$_ABC["abc"]'} <span style="color:green">正确</span><br/>
		   {$dog = 'It's a dog'} <span style="color:red">错误</span><br/>
		   {$dog = 'It\'s a dog'} <span style="color:green">正确</span><br/>
		  <br/><br/>
          <span style="color:red">务必注意，在语体中使用{}必须将其转义。</span><br/>
		  在语体将{}转义的方法是使用\{和\}<br/>
		  比如：<br/>
		  {$abc= '\{asdfa\}'}<?php $this->_data['abc']='{asdfa}'; ?>将会输出结果：<?php echo $this->_data['abc']; ?><br/>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">变量输出</span><br/><br/>
		 <span style="color:blue">语法: {变量名}</span><br/></br>
		 已定义变量：{$title}　　　
		 输出：<?php echo $this->_data['title']; ?><br/>

         未定义变量：{$name}　　　
	  输出：{$name}<br/>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;height:420px;">
		 <div style="float:left;width:760px;"><span style="font-size:14px;color:blue;font-weight:bold;">循环</span></div><br/><br/>
		 <span style="color:blue">语法: <br/>
		 {loop 变量名 输出数组 }<br/>
		　		//循环块<br/>
		 {/loop}</span>
		 </br><br/>
		 例如：<br/><span style="color:brown">{loop $students $std }<br/>
		     　姓名:{$std['name']} <br/>
			 　年龄:{$std['age']} <br/>
			 　成绩:<br/>
			 　{loop $std['score'] $score } <span style="color:#000">// 嵌套的循环</span><br/>
			 　　　{$score['subject']} : {$score['score']} <br/>
			 　{/loop}<br/>
         {/loop}<br/><br/></span>
		 <span style="color:blue">输出：<br/><br/></span>
         <?php if(is_array($this->_data['students'])){ foreach ($this->_data['students'] as $this->_data['std'] ) { ?>
		    <div style="float:left;width:220px;">
		     姓名:<?php echo $this->_data['std']['name']; ?> <br/>
			 年龄:<?php echo $this->_data['std']['age']; ?> <br/>
			 成绩:<br/>
			 <?php if(is_array($this->_data['std']['score'])){ foreach ($this->_data['std']['score'] as $this->_data['score'] ) { ?>
			 　　<?php echo $this->_data['score']['subject']; ?> : <?php echo $this->_data['score']['score']; ?> <br/>
			 <?php }} ?>
			 </div>
         <?php }} ?>
		 <br/><br/>
		 HET支持在循环中使用continue与break，请看下一块的逻辑处理中的例子。
	</div>
   <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">逻辑处理</span><br/><br/>
		 <span style="color:blue">语法: <br/>
		 {if|elseif|while 条件}</span><br/>
		 　statement...<br/>
         <span style="color:blue">{/if|while}</span> <br/>
		 注：条件语体不需要用()括起来, if 或 elseif开头的以{/if}结束,{while}开始的则以{/while}结束
		 </br></br>
		 例如：<br/>
		 <span style="color:brown">{ $gender = 'female'}<?php $this->_data['gender']='female'; ?><br/>{if $gender == 'male' }<br/>
					　<span style="color:#000">He is Male.</span><br/>
			    {elseif $gender == 'female'}<br/>
					　<span style="color:#000">She is Female.</span><br/>
				{else}<br/>
				　<span style="color:#000">Unknow.</span><br/>
				{/if}</span><br/><br/>
		输出：<?php if($this->_data['gender'] == 'male'){ ?><br/>
					　He is Male.<br/>
			    <?php }elseif($this->_data['gender'] == 'female'){ ?><br/>
					　She is Female.<br/>
			    <?php }else{ ?>
						Unknow.
				<?php } ?>
		<br/><br/>
		使用while的例子：<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    　{$i = $i+1}<br/>
			　{$i}<br/>
		{/while}<br/></span>
		输出：
		<?php $this->_data['i']=0; ?>
		<?php while($this->_data['i'] < 10){ ?>
		    <?php $this->_data['i']=$this->_data['i']+1; ?>
			<?php echo $this->_data['i']; ?>
		<?php } ?><br/><br/>

		使用continue的例子(不输出5)：<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    　{$i = $i+1}<br/>
			 {if $i == 5 }{continue}{/if}<br/>
			　{$i}<br/>
		{/while}<br/></span>
		输出：
		<?php $this->_data['i']=0; ?>
		<?php while($this->_data['i'] < 10){ ?>
		    <?php $this->_data['i']=$this->_data['i']+1; ?>
			<?php if($this->_data['i'] == 5){ ?>
			<?php continue; ?>  
			<?php } ?>
			<?php echo $this->_data['i']; ?>
		<?php } ?><br/><br/>

		使用break的例子(不输出4以后的)：<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    　{$i = $i+1}<br/>
			 {if $i == 5 }{break}{/if}<br/>
			　{$i}<br/>
		{/while}<br/></span>
		输出：
		<?php $this->_data['i']=0; ?>
		<?php while($this->_data['i'] < 10){ ?>
		    <?php $this->_data['i']=$this->_data['i']+1; ?>
			<?php if($this->_data['i'] == 5){ ?>
			<?php break; ?>  
			<?php } ?>
			<?php echo $this->_data['i']; ?>
		<?php } ?>
	</div>

   <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">使用函数</span><br/><br/>
		 <span style="color:blue">语法: { 函数名() }</span><br/></br>
		 例如：<span style="color:brown">{myfunc()}</span><br/>
		 输出：<?php myfunc(); ?><br/><br/>
		 在其他语体中使用函数：<br/>
		 <span style="color:brown">{$date = date('Y-m-d')}</span> // date()函数赋值给$date<br/>
		 <span style="color:brown">{$date}</span> <br/>
		 <?php $this->_data['date']=date('Y-m-d'); ?>
		 输出：<?php echo $this->_data['date']; ?><br/><br/>
		 带参数使用：
		 <span style="color:brown">{myfunc( $date )}</span><br/>
		 输出：<?php myfunc($this->_data['date']); ?><br/><br/>
         <span style="color:brown">{$number = 1234.567 }<?php $this->_data['number']=1234.567; ?><br/>
         {$numberf = number_format($number , 2 )}<?php $this->_data['numberf']=number_format($this->_data['number'] , 2 ); ?><br/>
		 {$numberf}</br></span>
		 输出：<?php echo $this->_data['numberf']; ?>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">赋值</span><br/><br/>
		 <span style="color:blue">语法: {变量名 = 值}</span><br/></br>
		 数字赋值：{ $n = 123 } <?php $this->_data['n']=123; ?>　　　　
		 输出：<?php echo $this->_data['n']; ?><br/><br/>

         字符串赋值：{ $abc = 'abc' } <?php $this->_data['abc']='I am abc'; ?>　　　　
		 输出：<?php echo $this->_data['abc']; ?><br/><br/>

		 变量赋值：{ $ABC = $abc.' too , Value comes from $abc.' }<?php $this->_data['ABC']=$this->_data['abc'].' too , Value comes from $abc.'; ?><?php $this->_data['ABC']=$this->_data['abc'].' too , Value comes from $abc.'; ?>　　
          输出：<?php echo $this->_data['ABC']; ?><br/><br/>
         注册函数赋值：{ $date = date('Y-m-d H:i:s') } <?php $this->_data['date']=date('Y-m-d H:i:s'); ?>　　　　
		 输出：<?php echo $this->_data['date']; ?>
	</div>

    <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">include的使用</span><br/><br/>
		 <span style="color:blue">语法: {include("文件名")}</span><br/>
		 include的使用依赖于PHP程序的注册函数，注册了include后即可使用。请使用小写　
		 </br></br>
		 　　
		 例如：{include("included.php")}<br/><br/>

         结果：<?php include("included.php"); ?><br/>
	</div>

     <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">$_REQUEST的使用</span><br/><br/>
		 <span style="color:blue">语法: 直接使用，键名不能有单引号</span><br/>
		 $_REQUEST的使用依赖于PHP程序。　
		 </br></br>
		 　　
		 例如：{ $id = $_REQUEST["id"] }<?php $this->_data['id']=$this->_data['_REQUEST']["id"]; ?> 在地址栏上加上?id=123输出{$id}<br/><br/>

         结果：<?php echo $this->_data['id']; ?><br/>
	</div>



	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">转义语体</span><br/><br/>

		 <span style="color:blue">语法: \{ 内容 \}</span><br/></br>
		 转义后将输出 { 内容 }，当且仅当需要输出正确的语体时才需要使用转义，转义意味着语体不会被编译。
	</div>
</div>
</body>
</html>
