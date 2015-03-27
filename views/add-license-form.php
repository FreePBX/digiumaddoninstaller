<h3><?php echo $addon['name']?></h3>
<p><?php echo $addon['description']?></p>
<?php
// Avoid XSS
$addon = htmlentities($_GET['addon'], ENT_QUOTES);
$key = htmlentities($_POST['add_license_key'], ENT_QUOTES);
$name = "add_license_".htmlentities($uf['name'], ENT_QUOTES);
$aln = htmlentities($_POST[$name], ENT_QUOTES);
?>


<form name="add_license_form" method="post" action="config.php?type=setup&display=digiumaddons&page=add-license-form&addon=<?php echo $addon; ?>">
<fieldset>
<div class="error_msg"><?php echo $key_error_msg?></div>
<legend> Key </legend>
<div class="add_license_field">
	<label for="add_license_key">Key: </label>
	<input type="text" name="add_license_key" value="<?php echo $key; ?>" />
	*
</div>

</fieldset>
<fieldset>
<div class="error_msg"><?php echo $fields_error_msg?></div>
<legend>User Fields</legend>
<?php foreach ($product['userfields'] as $uf): ?>
<div class="add_license_field">
	<label for="<?php echo $name; ?>"><?php echo $uf['desc']?></label>
	<input type="text" name="<?php echo $name; ?>" value="<?php echo $aln; ?>" />
	<?php echo (($uf['required'])?"*":"")?>
</div>
<?php endforeach; ?>
</fieldset>
<input type="submit" name="add_license_submit" value="Submit" />
<input id="add_license_cancel" type="button" value="Cancel" />
</form>
<script type="text/javascript">
	$('#add_license_cancel').click(function() {
		window.location = "config.php?type=setup&display=digiumaddons";
	});
</script>
