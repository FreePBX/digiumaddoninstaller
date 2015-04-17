<h3><?php echo $addon['name']?></h3>
<?php
// Avoid XSS
$addon = htmlentities($_GET['addon'], ENT_QUOTES);
$regkey = htmlentities($_GET['register_key'], ENT_QUOTES);
$val = htmlentities($_POST[$regname]);
?>
<form name="register_form" method="post" action="config.php?type=setup&display=digiumaddons&page=register-form&addon=<?php echo $addon; ?>">
<fieldset>
<div id="error_msg"><?php echo $error_msg?></div>
<legend> Key </legend>
<div class="register_field">
	<label for="register_key">Key: </label>
	<input type="text" name="register_key" value="<?php echo $regkey; ?>" />
	*
</div>

</fieldset>
<fieldset>
<legend>User Fields</legend>
<?php foreach ($product['userfields'] as $uf): 
	$regname = "register_".htmlentities($uf['name']);
?>
<div class="register_field">
	<label for="register_<?php echo $uf['name']?>"><?php echo $uf['desc']?></label>
	<input type="text" name="<?php echo $regname; ?>" value="<?php echo $val; ?>" />
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
