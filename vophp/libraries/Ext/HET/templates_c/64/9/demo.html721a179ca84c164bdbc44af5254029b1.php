<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
 <head>
  <title><?php echo $this->_data['title']; ?></title>
  <meta name="Keywords" content="HET,ģ������,PHP,Hellex">
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
	font: 12px ����, ������, Tahoma, Verdana;
	}
  </style>
 </head>
<body>
<div id="whole" style="margin:12px;border:1px solid #F5F5F5;width:780px;">
	<div style="margin:12px;width:760px;">
		 <span style="font-size:22px;color:blue;font-weight:bold;">HET Usage (����ƪ)</span><br/><br>
		 (���ļ���<a href="demo.php">demo.php</a>��html������)</br>
		 by <span style="color:blue;"><a href="mailto:hellex@live.com">Hellex</a></span>	 
	</div>

   <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">ע������</span><br/><br/>
		  HET�Ľ��������У��ڵ�������ʹ�õ����ţ����뽫������\ת��<br/>
		  ���� <br/>
		  {$abc = '$_ABC['abc']'} <span style="color:red">����</span><br/>
		  {$abc = '$_ABC[\'abc\']'} <span style="color:green">��ȷ</span><br/>
		  {$abc = '$_ABC["abc"]'} <span style="color:green">��ȷ</span><br/>
		   {$dog = 'It's a dog'} <span style="color:red">����</span><br/>
		   {$dog = 'It\'s a dog'} <span style="color:green">��ȷ</span><br/>
		  <br/><br/>
          <span style="color:red">���ע�⣬��������ʹ��{}���뽫��ת�塣</span><br/>
		  �����彫{}ת��ķ�����ʹ��\{��\}<br/>
		  ���磺<br/>
		  {$abc= '\{asdfa\}'}<?php $this->_data['abc']='{asdfa}'; ?>������������<?php echo $this->_data['abc']; ?><br/>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">�������</span><br/><br/>
		 <span style="color:blue">�﷨: {������}</span><br/></br>
		 �Ѷ��������{$title}������
		 �����<?php echo $this->_data['title']; ?><br/>

         δ���������{$name}������
	  �����{$name}<br/>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;height:420px;">
		 <div style="float:left;width:760px;"><span style="font-size:14px;color:blue;font-weight:bold;">ѭ��</span></div><br/><br/>
		 <span style="color:blue">�﷨: <br/>
		 {loop ������ ������� }<br/>
		��		//ѭ����<br/>
		 {/loop}</span>
		 </br><br/>
		 ���磺<br/><span style="color:brown">{loop $students $std }<br/>
		     ������:{$std['name']} <br/>
			 ������:{$std['age']} <br/>
			 ���ɼ�:<br/>
			 ��{loop $std['score'] $score } <span style="color:#000">// Ƕ�׵�ѭ��</span><br/>
			 ������{$score['subject']} : {$score['score']} <br/>
			 ��{/loop}<br/>
         {/loop}<br/><br/></span>
		 <span style="color:blue">�����<br/><br/></span>
         <?php if(is_array($this->_data['students'])){ foreach ($this->_data['students'] as $this->_data['std'] ) { ?>
		    <div style="float:left;width:220px;">
		     ����:<?php echo $this->_data['std']['name']; ?> <br/>
			 ����:<?php echo $this->_data['std']['age']; ?> <br/>
			 �ɼ�:<br/>
			 <?php if(is_array($this->_data['std']['score'])){ foreach ($this->_data['std']['score'] as $this->_data['score'] ) { ?>
			 ����<?php echo $this->_data['score']['subject']; ?> : <?php echo $this->_data['score']['score']; ?> <br/>
			 <?php }} ?>
			 </div>
         <?php }} ?>
		 <br/><br/>
		 HET֧����ѭ����ʹ��continue��break���뿴��һ����߼������е����ӡ�
	</div>
   <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">�߼�����</span><br/><br/>
		 <span style="color:blue">�﷨: <br/>
		 {if|elseif|while ����}</span><br/>
		 ��statement...<br/>
         <span style="color:blue">{/if|while}</span> <br/>
		 ע���������岻��Ҫ��()������, if �� elseif��ͷ����{/if}����,{while}��ʼ������{/while}����
		 </br></br>
		 ���磺<br/>
		 <span style="color:brown">{ $gender = 'female'}<?php $this->_data['gender']='female'; ?><br/>{if $gender == 'male' }<br/>
					��<span style="color:#000">He is Male.</span><br/>
			    {elseif $gender == 'female'}<br/>
					��<span style="color:#000">She is Female.</span><br/>
				{else}<br/>
				��<span style="color:#000">Unknow.</span><br/>
				{/if}</span><br/><br/>
		�����<?php if($this->_data['gender'] == 'male'){ ?><br/>
					��He is Male.<br/>
			    <?php }elseif($this->_data['gender'] == 'female'){ ?><br/>
					��She is Female.<br/>
			    <?php }else{ ?>
						Unknow.
				<?php } ?>
		<br/><br/>
		ʹ��while�����ӣ�<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    ��{$i = $i+1}<br/>
			��{$i}<br/>
		{/while}<br/></span>
		�����
		<?php $this->_data['i']=0; ?>
		<?php while($this->_data['i'] < 10){ ?>
		    <?php $this->_data['i']=$this->_data['i']+1; ?>
			<?php echo $this->_data['i']; ?>
		<?php } ?><br/><br/>

		ʹ��continue������(�����5)��<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    ��{$i = $i+1}<br/>
			 {if $i == 5 }{continue}{/if}<br/>
			��{$i}<br/>
		{/while}<br/></span>
		�����
		<?php $this->_data['i']=0; ?>
		<?php while($this->_data['i'] < 10){ ?>
		    <?php $this->_data['i']=$this->_data['i']+1; ?>
			<?php if($this->_data['i'] == 5){ ?>
			<?php continue; ?>  
			<?php } ?>
			<?php echo $this->_data['i']; ?>
		<?php } ?><br/><br/>

		ʹ��break������(�����4�Ժ��)��<br/>
		<span style="color:brown">{$i=0}<br/>
		{while $i < 10 }<br/>
		    ��{$i = $i+1}<br/>
			 {if $i == 5 }{break}{/if}<br/>
			��{$i}<br/>
		{/while}<br/></span>
		�����
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
		 <span style="font-size:14px;color:blue;font-weight:bold;">ʹ�ú���</span><br/><br/>
		 <span style="color:blue">�﷨: { ������() }</span><br/></br>
		 ���磺<span style="color:brown">{myfunc()}</span><br/>
		 �����<?php myfunc(); ?><br/><br/>
		 ������������ʹ�ú�����<br/>
		 <span style="color:brown">{$date = date('Y-m-d')}</span> // date()������ֵ��$date<br/>
		 <span style="color:brown">{$date}</span> <br/>
		 <?php $this->_data['date']=date('Y-m-d'); ?>
		 �����<?php echo $this->_data['date']; ?><br/><br/>
		 ������ʹ�ã�
		 <span style="color:brown">{myfunc( $date )}</span><br/>
		 �����<?php myfunc($this->_data['date']); ?><br/><br/>
         <span style="color:brown">{$number = 1234.567 }<?php $this->_data['number']=1234.567; ?><br/>
         {$numberf = number_format($number , 2 )}<?php $this->_data['numberf']=number_format($this->_data['number'] , 2 ); ?><br/>
		 {$numberf}</br></span>
		 �����<?php echo $this->_data['numberf']; ?>
	</div>

	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">��ֵ</span><br/><br/>
		 <span style="color:blue">�﷨: {������ = ֵ}</span><br/></br>
		 ���ָ�ֵ��{ $n = 123 } <?php $this->_data['n']=123; ?>��������
		 �����<?php echo $this->_data['n']; ?><br/><br/>

         �ַ�����ֵ��{ $abc = 'abc' } <?php $this->_data['abc']='I am abc'; ?>��������
		 �����<?php echo $this->_data['abc']; ?><br/><br/>

		 ������ֵ��{ $ABC = $abc.' too , Value comes from $abc.' }<?php $this->_data['ABC']=$this->_data['abc'].' too , Value comes from $abc.'; ?><?php $this->_data['ABC']=$this->_data['abc'].' too , Value comes from $abc.'; ?>����
          �����<?php echo $this->_data['ABC']; ?><br/><br/>
         ע�ắ����ֵ��{ $date = date('Y-m-d H:i:s') } <?php $this->_data['date']=date('Y-m-d H:i:s'); ?>��������
		 �����<?php echo $this->_data['date']; ?>
	</div>

    <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">include��ʹ��</span><br/><br/>
		 <span style="color:blue">�﷨: {include("�ļ���")}</span><br/>
		 include��ʹ��������PHP�����ע�ắ����ע����include�󼴿�ʹ�á���ʹ��Сд��
		 </br></br>
		 ����
		 ���磺{include("included.php")}<br/><br/>

         �����<?php include("included.php"); ?><br/>
	</div>

     <div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">$_REQUEST��ʹ��</span><br/><br/>
		 <span style="color:blue">�﷨: ֱ��ʹ�ã����������е�����</span><br/>
		 $_REQUEST��ʹ��������PHP���򡣡�
		 </br></br>
		 ����
		 ���磺{ $id = $_REQUEST["id"] }<?php $this->_data['id']=$this->_data['_REQUEST']["id"]; ?> �ڵ�ַ���ϼ���?id=123���{$id}<br/><br/>

         �����<?php echo $this->_data['id']; ?><br/>
	</div>



	<div style="margin:10px;padding:4px;margin-top:22px;background:#ccc;width:760px;text-align:left;">
		 <span style="font-size:14px;color:blue;font-weight:bold;">ת������</span><br/><br/>

		 <span style="color:blue">�﷨: \{ ���� \}</span><br/></br>
		 ת������ { ���� }�����ҽ�����Ҫ�����ȷ������ʱ����Ҫʹ��ת�壬ת����ζ�����岻�ᱻ���롣
	</div>
</div>
</body>
</html>
