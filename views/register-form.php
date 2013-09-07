<h3><?php echo $addon['name']?></h3>
<form name="register_form" method="post" action="config.php?type=setup&display=digiumaddons&page=register-form&addon=<?php echo $_GET['addon']?>">
<fieldset>
<div id="error_msg"><?php echo $error_msg?></div>
<legend> Key </legend>
<div class="register_field">
	<label for="register_key">Key: </label>
	<input type="text" name="register_key" value="<?php echo $_POST['register_key']?>" />
	*
</div>

</fieldset>
<fieldset>
<legend>User Fields</legend>
<?php foreach ($product['userfields'] as $uf): ?>
<div class="register_field">
	<label for="register_<?php echo $uf['name']?>"><?php echo $uf['desc']?></label>
	<input type="text" name="register_<?php echo $uf['name']?>" value="<?php echo $_POST['register_'.$uf['name']]?>" />
	<?php echo (($uf['required'])?"*":"")?>
</div>
<?php endforeach; ?>
</fieldset>
<input type="submit" name="register_submit" value="Submit" />
<input id="register_cancel" type="button" value="Cancel" />
</form>
<script type="text/javascript">
	$('#register_cancel').click(function() {
		window.location = "config.php?type=setup&display=digiumaddons";
	});
</script>
