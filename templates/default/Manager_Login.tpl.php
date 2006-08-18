<p>Welcome to the University Event Publishing System, please log in using your My.UNL (Blackboard/LDAP) Username and Password.</p>
<?php
	$form = new HTML_QuickForm('login');
	$form->addElement('text',$this->user_field,'User');
	$form->addElement('password',$this->password_field,'Password');
	$form->addElement('submit','submit','Submit');
	$renderer =& new HTML_QuickForm_Renderer_Tableless();
	$form->accept($renderer);
	echo $renderer->toHtml();
?>
<p id="lost"><a href="http://my.unl.edu/webapps/blackboard/password" title="" id="forgot">Forgot your password?</a></p>