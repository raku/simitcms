<?php
include_once('../../config.php');
include_once('admin.class.php');

/*-----Config Settings-----*/
$list=5;
/*-----Config Settings-----*/

if(empty($_GET)) {
	$ic.='<ul>';
		$ic.=customlist();
	$ic.='</ul>';
	
	include("../template.php");
	exit;
}

if (isset($_GET['c']) && is_numeric($_GET['c'])) {
	$table='custom'.$_GET['c'];
	$cid=$_GET['c'];
	$vartype=unserialize(mysql_result(mysql_query("SELECT setting_value FROM settings WHERE setting_owner = 'custom_vartype' AND setting_name='$table'"), 0));
}

$message[1]='New entry created successfully';
$message[2]='The entry edited successfully';
$message[3]='The entry deleted successfully';
$message[4]='Settings deleted successfully';
$message[5]="I can't create new entry";
$message[6]="I can't edit the entry";
$message[7]="I can't delete the entry";
$message[8]="I can't update settings";

if (isset($_GET['message']) && is_numeric($_GET['message'])) {
$not='<div class="box success" onload="">'.$message[$_GET["message"]].'</div>';
}

// Start Get Column Information
$field = getcolumns($table);
$ai = getai($table);
$column = count($field);
// Finish Get Column Information

if(!isset($_GET["settings"]) && !isset($_GET["new"]) && empty($_GET["edit"])) {
$sq = searchsql($field);
$howmany = mysql_result(mysql_query("SELECT COUNT(*) FROM $table $sq"), 0); // The number of total rows
$pag = new Pagination($howmany,$list);
$pag->getpage();

if(isset($_GET["search"])) {
$search = $_GET["search"];
$ic.="<a class='awesome light' style='float:left;'>'$search' Sorgusu için toplam $howmany kayıt bulundu.</a><a class='awesome light' style='float:left; margin-left:2px;' href='".$_SERVER["SCRIPT_NAME"]."'>x</a>";
}else {
$ic.='<a class="button orange left" href="?c='.$cid.'&new">Yeni Girdi Ekle</a>';
$ic.="<a class='button purple leftmargin left' href='?c=$cid&settings' >&#8501; &#948; </a>";
}

$ic.="
<form method='GET' value=''>
<input style='float:right;' type='submit' value='Search'>
<input style='float:right;' type='text' name='search'>
<input type='hidden' name='c' value='$cid'>
</form>";

// Column's Names
$ic.='
<table cellspacing="0">
<th><input type="checkbox"></th>';
for($i=0; $i<$column; $i++){{$ic.='<th class="'.$vartype[$i].'"><p>'.$field[$i]['comment'].'</p></th>';}}
$ic.='<th></th></tr>';

$ask = mysql_query("SELECT * FROM $table $sq ORDER BY `$i` DESC LIMIT".$pag->sqlcode());

while($b = mysql_fetch_assoc($ask)) {
$ic.="<tr>";
$ic.="<td><input type='checkbox' name='".$b[$ai]."'></td>";
for($i=0; $i<$column; $i++) {
	if    ($vartype[$i]=='date') {$ic.="<td class='date'>".htmlentities($b[$field[$i]['name']], ENT_QUOTES, "UTF-8").'</td>';}
	elseif($vartype[$i]=='image') {$ic.="<td class='image'> <a href='".$b[$field[$i]['name']]."' id='effect' alt='das'><img src='".$b[$field[$i]['name']]."' class='thumb' onerror='this.src=\"images/picture_error.png\"'></a></td>";}
	elseif($vartype[$i]=='text') {$ic.="<td>". short(100, strip_tags($b[$field[$i]['name']])).'</td>';}
	elseif($vartype[$i]=='textarea') {$ic.="<td>". short(100, strip_tags($b[$field[$i]['name']])).'</td>';}
	else  {$ic.="<td>".htmlentities($b[$field[$i]], ENT_QUOTES, "UTF-8").'</td>';}
}
$ic.="<td>
<a href='?c=".$cid."&edit=".$b[$ai]."'><img src='images/plugin_edit.png'></a>
<a href='?c=".$cid."&delete=".$b[$ai]."' onclick=\"return confirm('Bu girdi geri dönüşümsüz biçimde silinecek, emin misiniz?');\"><img src='images/plugin_delete.png'></a></td></tr>";
}
$ic.='</table>';
$ic.= $pag->write();
}

// Start New Entry
if(isset($_GET["new"])){
if(isset($_POST["var0"])) {
$dbcolumn=NULL;
$var=NULL;
for($i=0; $i<$column; $i++) {$dbcolumn.='`'.$field[$i].'`, '; $var.= "'".$_POST["var".($i)]."', ";}
$dbcolumn = rtrim($dbcolumn, ", ");
$var = rtrim($var, ", ");

ob_start();
mysql_query("INSERT INTO $table ($dbcolumn) VALUES ($var)") or die(header('Location: ?message=5'));
header('Location: ?message=1');
}

else{
// Create upload js code
for($i=0; $i<$column; $i++) {if($vartype[$i]=='image') {$up[]=$i;}}
for($i=0; $i<count($up); $i++) {
$uplo='
<script type="text/javascript">
$(function(){var b=$("#upload'.$up[$i].'");var a=$("#status");new AjaxUpload(b,{action:"gallery.php",name:"uploadfile",onSubmit:function(c,d){if(!(d&&/^(jpg|png|jpeg|gif)$/.test(d))){a.text("Only JPG, PNG or GIF files are allowed");return false}a.html("<|>")},onComplete:function(d,c){a.text("");if(c==="error"){$("<li></li>").appendTo(\'input[name="var'.$up[$i].'"]\').text(c).addClass("error")}else{$(\'input[name="var'.$up[$i].'"]\').val("'.SITE_ADDRESS.'/uploads/"+[c]), ImgError($(\'input[name="var'.$up[$i].'"]\'));}}})});
</script>
';
$head.=$uplo;
}

$ic.='<form method="POST" value="">';
for($i=0; $i<$column; $i++) {
if    ($vartype[$i]=='date') {$ic.="<p><input type='text' class='date' name='var$i'></p>";}
elseif($vartype[$i]=='image') {$imgupload=TRUE; $ic.="<p><input type='text' name='var$i' id='var$i' class='image'  onchange='ImgError($(this))'>".'<span id="upload'.$i.'" class="button orange" >Upload</span><span class="button orange leftmargin" id="customimgbrowse" alt="var'.$i.'">Browse</span><span id="status" ></span><span class="check"  id="check'.$i.'"></span></p>';}
elseif($vartype[$i]=='textarea') {$textarea=TRUE; $ic.="<p><textarea name='var$i' class='ckeditor' ></textarea></p>";}
else  {$ic.="<p><input type='text' class='text' name='var$i'  value=''></p>";}
}
$ic.='<input type="submit" value="Add"></form>';
}
}
// Finish New Entry

// Start Edit Entry
if(isset($_GET["edit"])) {
if(isset($_POST["edit"])) {
$id = $_POST["edit"];
$var=NULL;
for($i=0; $i<$column; $i++) {$var.='`'.$field[$i].'`= '; $var.= "'".html_entity_decode($_POST["var".($i)], ENT_QUOTES, "UTF-8")."', ";}
$var = rtrim($var, ", ");

ob_start();
mysql_query("UPDATE $table SET $varWHERE $ai[0]=$id") or die(header('Location: ?message=6&'.mysql_error()));
header('Location: ?message=2');
}

else{

// Create upload js code
for($i=0; $i<$column; $i++) {if($vartype[$i]=='image') {$up[]=$i;}}
for($i=0; $i<count($up); $i++) {
$uplo='
<script type="text/javascript">
$(function(){var b=$("#upload'.$up[$i].'");var a=$("#status");new AjaxUpload(b,{action:"gallery.php",name:"uploadfile",onSubmit:function(c,d){if(!(d&&/^(jpg|png|jpeg|gif)$/.test(d))){a.text("Only JPG, PNG or GIF files are allowed");return false}a.html("<|>")},onComplete:function(d,c){a.text("");if(c==="error"){$("<li></li>").appendTo(\'input[name="var'.$up[$i].'"]\').text(c).addClass("error")}else{$(\'input[name="var'.$up[$i].'"]\').val("'.SITE_ADDRESS.'/uploads/"+[c]), ImgError($(\'input[name="var'.$up[$i].'"]\'));}}})});
</script>
';
$head.=$uplo;
}

$id=$_GET["edit"];
$ask = mysql_query("SELECT * FROM $table WHERE $ai[0]=".$id);
while($b = mysql_fetch_assoc($ask)) {
$ic.='<form method="POST" value="">';
$ic.="<input type='hidden' name='edit' value='$id'>";
for($i=0; $i<$column; $i++) {
if    ($vartype[$i]=='date') {$ic.="<p><input type='text' class='date' name='var$i' value='".$b[$field[$i]]."'></p>";}
elseif($vartype[$i]=='image') {$imgupload=TRUE; $ic.="<p><input type='text' name='var$i' id='var$i' class='image' value='".$b[$field[$i]]."' onchange='ImgError($(this))'>".'<span id="upload'.$i.'" class="button orange" >Upload Image</span><span id="status" ></span><span class="check" id="check'.$i.'"></span></p>';}
elseif($vartype[$i]=='textarea') {$textarea=TRUE; $ic.="<p><textarea name='var$i' class='ckeditor' >".htmlspecialchars ($b[$field[$i]], ENT_QUOTES, "UTF-8")."</textarea></p>";}
else  {$ic.="<p><input type='text' class='text' name='var$i'  value='".$b[$field[$i]]."'></p>";}
}
$ic.='<input type="submit" value="Add"></form>';
}
}
}
// Finish Edit Entry

// Start Delete Entry
if(isset($_GET["delete"])) {
$del = $_GET["delete"];

ob_start();
mysql_query("DELETE FROM $table WHERE $ai[0]='$del'") or die(header('Location: ?message=7'));
header('Location: ?message=3');
}
// Finish Delete Entry

// Start Settings Area
if(isset($_GET["settings"])) {
if(isset($_POST["setvar1"])) {
for($i=0; $i<$column; $i++) {
$comment = $_POST["setvar".$i];

ob_start();
mysql_query("ALTER TABLE `$table` CHANGE `$field[$i]` `$field[$i]` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '$comment'") or die(header('Location: ?settings&message=8'));
header('Location: ?settings&message=4');
}
}

else{
$head.=<<<html
<script type="text/javascript">
    $( document ).ready( function( ) {
	$("input.disabled").qtip({
	content: $(this).attr('alt'),
	position: {
		corner: {
         target: 'rightMiddle',
         tooltip: 'leftMiddle'
		}
   },
   show: { effect:'fade', solo: true },
   hide: { when: 'mouseout', fixed: true },
   style: {
         border: {
         width: 3,
         radius: 3,
		},
      tip: 'leftMiddle',
      name: 'orange'
   }
});
        } );
</script>
html;

$ic.='<form value="" method="POST">';
for($i=0; $i<$column; $i++) {
$ic.="\n"."<input type='text' name='setvar$i' style='width:51%;' value='$name[$i]'>
	<input class='disabled' alt=\"Üzgünüz, burada yapılabilecek değişiklikler girdilerin işleyişini bozabileceğinden bu seçeneği devredışı bıraktık.\" type='text' style='width:11%;' READONLY value='$vartype[$i]'>";
}
$ic.='<input type="submit" value="Edit"></form>';
}
}
// Finish Settings Area

if(isset($_GET["gir"])) {for($i=1; $i<200; $i++) {mysql_query("INSERT INTO `admin`.`tablomuz` (`id`, `1`, `2`, `3`) VALUES (NULL, 'DSAD', 'ASDASD', 'SDA');") or die("Can't create new entry");}}

include("../template.php");
?>