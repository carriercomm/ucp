<?php /* Smarty version 2.6.20, created on 2010-09-01 12:14:14
         compiled from lostpw-request.tpl */ ?>
<form action="<?php echo $this->_tpl_vars['PHP_SELF']; ?>
" class="lostpw" method="post">
	<table>
	<tr>
		<th>User:</th>
		<td><input type="text" name="user" /></td>
	</tr>
	<tr>
		<th>Mail:</th>
		<td><input type="text" name="mail" /></td>
	</tr>
	<tr>
		<td colspan="2"><input class="submit" type="submit" value="anfordern" /></td>
	</tr>
	</table>
</form>