<?php

function informations_menu($arg) {
print "<a href='".esc_url( add_query_arg( 'module', 'informations', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='informations') { print " active";}
print "'>".__( 'Personal informations', 'doliconnect')."</a>";
}
add_action( 'user_doliconnect_menu', 'informations_menu', 1, 1);

function informations_module($url) {
global $wpdb, $current_user;

$ID = $current_user->ID;

$request = "/thirdparties/".doliconnector($current_user, 'fk_soc');

if ( isset($_POST["case"]) && $_POST["case"] == 'updateuser' ) {
 
$thirdparty=$_POST['thirdparty'][''.doliconnector($current_user, 'fk_soc').''];

if ( $thirdparty['morphy'] == 'mor' ) {
$thirdparty['tva_intra'] =strtoupper(sanitize_user($thirdparty['tva_intra']));
} else { $thirdparty['tva_intra'] = ''; }

if ( $thirdparty['morphy'] != 'mor' && get_option('doliconnect_disablepro') != 'mor' ) {
$thirdparty['name'] = ucfirst(strtolower($thirdparty['firstname']))." ".strtoupper($thirdparty['lastname']);
}

wp_update_user( array( 'ID' => $ID, 'user_email' => sanitize_email($thirdparty['email'])));
wp_update_user( array( 'ID' => $ID, 'nickname' => sanitize_user($_POST['user_nicename'])));
if (isset($thirdparty['name'])) wp_update_user( array( 'ID' => $ID, 'display_name' => sanitize_user($thirdparty['name'])));
wp_update_user( array( 'ID' => $ID, 'first_name' => ucfirst(sanitize_user(strtolower($thirdparty['firstname'])))));
wp_update_user( array( 'ID' => $ID, 'last_name' => strtoupper(sanitize_user($thirdparty['lastname']))));
wp_update_user( array( 'ID' => $ID, 'description' => sanitize_textarea_field($_POST['description'])));
wp_update_user( array( 'ID' => $ID, 'user_url' => sanitize_textarea_field($thirdparty['url'])));
update_user_meta( $ID, 'civility_id', sanitize_text_field($thirdparty['civility_id']));
update_user_meta( $ID, 'billing_type', sanitize_text_field($thirdparty['morphy']));
if ( $thirdparty['morphy'] == 'mor' ) { update_user_meta( $ID, 'billing_company', sanitize_text_field($thirdparty['name'])); }
update_user_meta( $ID, 'billing_birth', $thirdparty['birth']);

do_action('wp_dolibarr_sync', $thirdparty);

if ( isset($_GET['return']) ) {
wp_redirect(doliconnecturl('doliaccount').'?module='.$_GET['return']);
exit;
} else {
print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));
}
}

if ( isset($_GET['return']) ) {
$url = esc_url( add_query_arg( 'return', $_GET['return'], $url) );
}

if ( doliconnector($current_user, 'fk_soc') > '0' ) {
$thirdparty = callDoliApi("GET", $request, null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));  
}

print "<form action='".$url."' id='doliconnect-infosform' method='post' class='was-validated' enctype='multipart/form-data'><input type='hidden' name='case' value='updateuser'>";

print doliloaderscript('doliconnect-infosform');

print "<div class='card shadow-sm'>";

print doliuserform( $thirdparty, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null), true), 'thirdparty');

print "<div class='card-body'><input type='hidden' name='userid' value='$ID'><button class='btn btn-danger btn-block' type='submit'>".__( 'Update', 'doliconnect')."</button></div>";
print '<div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('thirdparty'), $thirdparty);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div></form>';

}
add_action( 'user_doliconnect_informations', 'informations_module');

function avatars_module($url) {
global $wpdb,$current_user;

$ID = $current_user->ID;
$time = current_time( 'timestamp', 1);

require_once ABSPATH . WPINC . '/class-phpass.php';

if ( isset($_POST["case"]) && $_POST["case"] == 'updateavatar' ) {

if ( isset($_POST['inputavatar']) && $_POST['inputavatar'] == 'delete' ) {

$upload_dir = wp_upload_dir();
$nam=$wpdb->prefix."member_photo";

$files = glob($upload_dir['basedir']."/doliconnect/".$ID."/*");
foreach($files as $file){
if(is_file($file))
unlink($file); 
}

delete_user_meta( $ID, $nam,$current_user->$nam);

if ( doliconnector($current_user, 'fk_member') > 0 ) {
$data = [
    'photo' => ''
	];
$adherent = callDoliApi("PUT", "/adherentsplus/".doliconnector($current_user, 'fk_member'), $data, dolidelay('member'));
}

} elseif ( $_FILES['inputavatar']['tmp_name'] != null ) {
$types = array('image/jpeg', 'image/jpg');
if ( $_FILES['inputavatar']['tmp_name'] != null ) {
list($width, $height) = getimagesize($_FILES['inputavatar']['tmp_name']);
}
if ( ( $width >= '350' && $height >= '350' ) && ( isset($_FILES['inputavatar']['tmp_name'])) && (in_array($_FILES['inputavatar']['type'], $types)) && ($_FILES['inputavatar']['size'] <= 10000000)) {

$upload_dir = wp_upload_dir();
$nam=$wpdb->prefix."member_photo";

if (file_exists($upload_dir['basedir']."/doliconnect/".$ID."/".$current_user->$nam)){
$files = glob($upload_dir['basedir']."/doliconnect/".$ID."/*");
foreach($files as $file){
if(is_file($file))
unlink($file); 
}}

if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php');
$uploadedfile = $_FILES['inputavatar'];
   
add_filter('wp_handle_upload_prefilter', 'custom_upload_filter');
function custom_upload_filter( $file ){

    $file['name'] = "avatar.jpg";
    return $file;
}

function dolipropal_upload_dir($fileup) {
	$fileup['subdir']		= '/doliconnect/'.$_POST["userid"];
	$fileup['path']		= $fileup['basedir'] . $fileup['subdir'];
	$fileup['url']		= $fileup['baseurl'] . $fileup['subdir'];
return $fileup;
}
 
$upload_overrides = array( 'test_form' => false );
add_filter('upload_dir', 'dolipropal_upload_dir');
$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
remove_filter('upload_dir', 'dolipropal_upload_dir');

$filename=$upload_dir['basedir']."/doliconnect/".$ID."/avatar.jpg";
$img = wp_get_image_editor($filename);
 
if ( ! is_wp_error( $img ) ) {
$exif = exif_read_data($filename);               
if ( isset($exif['Orientation']) && $exif['Orientation'] == '8') {
$img->rotate( 90 );
} elseif ( isset($exif['Orientation']) && $exif['Orientation'] == '3' ) {
$img->rotate( 180 );
} elseif ( isset($exif['Orientation']) && $exif['Orientation'] == '6' ) {
$img->rotate( -90 );
} 

$img->resize( 350, 350, true );
$avatar = $img->generate_filename($time,$upload_dir['basedir']."/doliconnect/".$ID."/", NULL );
$img->save($avatar);
update_user_meta( $_POST["userid"], $wpdb->prefix."member_photo","avatar-$time.jpg");
$filename2=$upload_dir['basedir']."/doliconnect/".$ID."/avatar-$time.jpg";
$img = wp_get_image_editor($filename2);
$img->resize( 72, 72, true );
$avatar1 = $img->generate_filename('72x72',$upload_dir['basedir']."/doliconnect/".$ID."/", NULL );
$img->save($avatar1);
$img = wp_get_image_editor($filename2);
$img->resize( 150, 150, true );
$avatar2 = $img->generate_filename('150x150',$upload_dir['basedir']."/doliconnect/".$ID."/", NULL );
$img->save($avatar2);
if ( file_exists($filename) ) {
unlink($filename);
}
}

$minifile=$upload_dir['basedir']."/doliconnect/".$ID."/avatar-$time-72x72.jpg";
$smallfile=$upload_dir['basedir']."/doliconnect/".$ID."/avatar-$time-150x150.jpg";
$avatarfile=$upload_dir['basedir']."/doliconnect/".$ID."/avatar-$time.jpg";

if ( file_exists($avatarfile) ) {
$imgData = base64_encode(file_get_contents("$avatarfile"));
$datat = [
  'filename' => 'avatar.jpg',
  'modulepart' => 'member',
  'subdir' => doliconnector($current_user, 'fk_member').'/photos',
  'filecontent' => $imgData,
  'fileencoding' => 'base64',
  'overwriteifexists'=> 1
	];
$photo = callDoliApi("POST", "/documents/upload", $datat, 0);
}
if ( file_exists($minifile) ) {
$imgData = base64_encode(file_get_contents("$minifile"));
$datat = [
  'filename' => 'avatar_mini.jpg',
  'modulepart' => 'member',
  'subdir' => doliconnector($current_user, 'fk_member').'/photos/thumbs',
  'filecontent' => $imgData,
  'fileencoding' => 'base64',
  'overwriteifexists'=> 1
	];
$photo = callDoliApi("POST", "/documents/upload", $datat, 0);
}
if ( file_exists($smallfile) ) {
$imgData = base64_encode(file_get_contents("$smallfile"));
$datat = [
  'filename' => 'avatar_small.jpg',
  'modulepart' => 'member',
  'subdir' => doliconnector($current_user, 'fk_member').'/photos/thumbs',
  'filecontent' => $imgData,
  'fileencoding' => 'base64',
  'overwriteifexists'=> 1
	];
$photo = callDoliApi("POST", "/documents/upload", $datat, 0);
}

if ( doliconnector($current_user, 'fk_member') > 0 ) {
$data = [
    'photo' => 'avatar.jpg'
	];
$adherent = callDoliApi("PUT", "/adherentsplus/".doliconnector($current_user, 'fk_member'), $data, dolidelay('member'));
}

} else {
print dolialert ('warning', "Votre photo n'a pu être chargée. Elle doit obligatoirement être au format .jpg et faire moins de 10 Mo. Taille minimum requise 350x350 pixels.");
}
}

print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));
}

print "<form action='".$url."' id='doliconnect-avatarform' method='post' class='was-validated' enctype='multipart/form-data'><input type='hidden' name='case' value='updateavatar'>";

print doliloaderscript('doliconnect-avatarform');

print "<div class='card shadow-sm'><ul class='list-group list-group-flush'>";
print "<li class='list-group-item'>";
print "<label for='description'><small>".__( 'Profile Picture', 'doliconnect')."</small></label><div class='form-group'>
<div class='input-group mb-2'><div class='input-group-prepend'><span class='input-group-text'><i class='fas fa-camera fa-fw'></i></span></div><div class='custom-file'>
<input type='file' name='inputavatar' class='custom-file-input' id='customFile' accept='image/*' ";
$table_prefix = $wpdb->get_blog_prefix( get_current_blog_id() ); 
$upload_dir = wp_upload_dir();
$nam=$table_prefix."member_photo";
if ( null == $current_user->$nam && doliconnector($current_user, 'fk_member') ) {
//print " required='required'";
}
print " capture><label class='custom-file-label' for='customFile' data-browse='".__( 'Browse', 'doliconnect')."'>".__( 'Select a file', 'doliconnect')."</label></div></div>
<small id='infoavatar' class='form-text text-muted text-justify'>".__( 'Your avatar must be a .jpg/.jpeg file, <10Mo and 350x350pixels minimum.', 'doliconnect')."</SMALL>";
print "<div class='custom-control custom-checkbox my-1 mr-sm-2'>
    <input type='checkbox' class='custom-control-input' id='inputavatar' name='inputavatar' value='delete' ";
if ( null == $current_user->$nam ) {
print " disabled='disabled'";
}
print "><label class='custom-control-label' for='inputavatar'>".__( 'Delete your picture', 'doliconnect')."</label></div></div>";
print "</li>";
print "</ul><div class='card-body'><input type='hidden' name='userid' value='$ID'><button class='btn btn-danger btn-block' type='submit'>".__( 'Update', 'doliconnect')."</button></div>";
print '<div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('thirdparty'), $thirdparty);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div></form>';

}
add_action( 'user_doliconnect_avatars', 'avatars_module');

function contacts_menu($arg) {
print "<a href='".esc_url( add_query_arg( 'module', 'contacts', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ( $arg == 'contacts' ) { print " active"; }
print "'>".__( 'Manage address book', 'doliconnect')."</a>";
}
add_action( 'user_doliconnect_menu', 'contacts_menu', 2, 1);

function contacts_module($url){
global $current_user;

$request = "/contacts?sortfield=t.rowid&sortorder=ASC&limit=100&thirdparty_ids=".doliconnector($current_user, 'fk_soc')."&includecount=1&sqlfilters=t.statut=1";

if ( isset ($_POST['add_contact']) && $_POST['add_contact'] == 'new_contact' ) {
$contactv=$_POST['contact'][''.doliconnector($current_user, 'fk_soc').''];
$data = [
    'civility_id'  => $contactv['civility_id'],     
    'firstname' => ucfirst(sanitize_user(strtolower($contactv['firstname']))),
    'lastname' => strtoupper(sanitize_user($contactv['lastname'])),
    'socid' => doliconnector($current_user, 'fk_soc'),
    'poste' => sanitize_textarea_field($contactv['poste']), 
    'address' => sanitize_textarea_field($contactv['address']),    
    'zip' => sanitize_text_field($contactv['zip']),
    'town' => sanitize_text_field($contactv['town']),
    'country_id' => sanitize_text_field($contactv['country_id']),
    'email' => sanitize_email($contactv['email']),
    'birthday' => $contactv['birth'],
    'phone_pro' => sanitize_text_field($contactv['phone'])
	];
$contactv = callDoliApi("POST", "/contacts", $data, 0);
$listcontact = callDoliApi("GET", $request, null, dolidelay('contact', true));
if ( $contactv > 0 ) {
print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));
}
} elseif ( isset ($_POST['delete_contact']) && $_POST['delete_contact'] > 0 ) {
$contactv = callDoliApi("GET", "/contacts/".$_POST['delete_contact'], null, 0);
if ( $contactv->socid == doliconnector($current_user, 'fk_soc') ) {
// try deleting
$delete = callDoliApi("DELETE", "/contacts/".$contactv->id, null, 0);

print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));

} else {
// fail deleting
}
$listcontact = callDoliApi("GET", $request, null, dolidelay('contact', true));
} elseif ( isset ($_POST['update_contact']) && $_POST['update_contact'] > 0 ) {
$contactv=$_POST['contact'][''.$_POST['update_contact'].''];
$data = [
    'civility_id'  => $contactv['civility_id'],     
    'firstname' => ucfirst(sanitize_user(strtolower($contactv['firstname']))),
    'lastname' => strtoupper(sanitize_user($contactv['lastname'])),
    'socid' => doliconnector($current_user, 'fk_soc'),
    'poste' => sanitize_textarea_field($contactv['poste']), 
    'address' => sanitize_textarea_field($contactv['address']),    
    'zip' => sanitize_text_field($contactv['zip']),
    'town' => sanitize_text_field($contactv['town']),
    'country_id' => sanitize_text_field($contactv['country_id']),
    'email' => sanitize_email($contactv['email']),
    'birthday' => $contactv['birth'],
    'phone_pro' => sanitize_text_field($contactv['phone'])
	];
$contactv = callDoliApi("PUT", "/contacts/".$_POST['update_contact'], $data, 0);
if ( $contactv->socid == doliconnector($current_user, 'fk_soc') ) {
// try deleting
print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));
} else {
// fail deleting
}
$listcontact = callDoliApi("GET", $request, null, dolidelay('contact', true));
} else {

$listcontact = callDoliApi("GET", $request, null, dolidelay('contact', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

}

if ( doliconnector($current_user, 'fk_soc') > 0 ) {
$thirdparty = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc'), null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));  
}

print "<form role='form' action='$url' id='doliconnect-contactform' method='post'>";
//$nonce = wp_create_nonce( 'my-nonce');

// This code would go in the target page.
// We need to verify the nonce.
//$nonce = $nonce;//$_REQUEST['_wpnonce'];
//if ( ! wp_verify_nonce( $nonce, 'my-nonce' ) ) {
    // This nonce is not valid.
 //   die( 'Security check'); 
//} else {

//print $nonce;
//}                    

print doliloaderscript('doliconnect-contactform');

print "<div class='card shadow-sm'><ul class='list-group list-group-flush'>";

if (empty($listcontact) || isset($listcontact->error)) {
$countContact = 0;
} else {
$countContact = count($listcontact);
}

if ( $countContact < 5 ) {
print '<button type="button" class="list-group-item lh-condensed list-group-item-action list-group-item-primary" data-toggle="modal" data-target="#addcontactadress"><center><i class="fas fa-plus-circle"></i> '.__( 'New contact', 'doliconnect').'</center></button>';
}

if ( !isset($listcontact->error) && $listcontact != null ) {
foreach ( $listcontact as $contact ) { 
$count=$contact->ref_facturation+$contact->ref_contrat+$contact->ref_commande+$contact->ref_propal;
print "<li class='list-group-item d-flex justify-content-between lh-condensed list-group-item-action'>";
print doliaddress($contact);
if (1 == 1) {

if ( doliversion('11.0.0') && isset($contact->roles) && $contact->roles != null ) {
foreach ( $contact->roles as $role ) { 
//print $role->label;
}}

print "<div class='col-4 col-sm-3 col-md-2 btn-group-vertical' role='group'>";
print "<button type='button' class='btn btn-light text-primary' data-toggle='modal' data-target='#contact-".$contact->id."' title='".__( 'Edit', 'doliconnect')." ".$contact->firstname." ".$contact->lastname."'><i class='fas fa-edit fa-fw'></i></a>
<button name='delete_contact' value='".$contact->id."' class='btn btn-light text-danger' type='submit' title='".__( 'Delete', 'doliconnect')." ".$contact->firstname." ".$contact->lastname."'><i class='fas fa-trash fa-fw'></i></button>";
print "</div>";
}
print "</li>";
}
} else {
print "<li class='list-group-item list-group-item-light'><center>".__( 'No contact', 'doliconnect')."</center></li>";
}

print '</ul>';
print "<div class='card-body'></div>";
print '<div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('contact'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div></form>';

if ( !isset($listcontact->error) && $listcontact != null ) {
foreach ( $listcontact as $contact ) { 

print '<div class="modal fade" id="contact-'.$contact->id.'" tabindex="-1" role="dialog" aria-labelledby="contact-'.$contact->id.'Title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
<div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content border-0"><div class="modal-header border-0">
<h5 class="modal-title" id="contact-'.$contact->id.'Title">'.__( 'Update contact', 'doliconnect').'</h5><button id="Closecontact'.$contact->id.'-form" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
<div id="contact'.$contact->id.'-form">';
print "<form class='was-validated' role='form' action='$url' name='contact".$contact->id."-form' method='post'>";

print dolimodalloaderscript('contact'.$contact->id.'-form');

print doliuserform($contact, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null), true), 'contact');

print "</div>".doliloading('contact'.$contact->id.'-form');
      
print "<div id='Footercontact".$contact->id."-form' class='modal-footer'><button name='update_contact' value='".$contact->id."' class='btn btn-warning btn-block' type='submit'>".__( 'Update', 'doliconnect')."</button></form></div>
</div></div></div>";
}}

if ( $countContact < 5 ) {

print "<div class='modal fade' id='addcontactadress' tabindex='-1' role='dialog' aria-labelledby='addcontactadressTitle' aria-hidden='true' data-backdrop='static' data-keyboard='false'>
<div class='modal-dialog modal-lg modal-dialog-centered' role='document'><div class='modal-content border-0'><div class='modal-header border-0'>
<h5 class='modal-title' id='addcontactadressTitle'>".__( 'New contact', 'doliconnect')."</h5><button id='Closeaddcontact-form' type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
</div><div id='addcontact-form'>";

print "<form class='was-validated' role='form' action='$url' name='addcontact-form' method='post'>";

print dolimodalloaderscript('addcontact-form');

print doliuserform($thirdparty, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null), true), 'contact');

print "</div>".doliloading('addcontact-form');

print "<div id='Footeraddcontact-form' class='modal-footer'><button name='add_contact' value='new_contact' class='btn btn-warning btn-block' type='submit'>".__( 'Add', 'doliconnect')."</button></form></div>
</div></div></div>";
}

}
add_action( 'user_doliconnect_contacts', 'contacts_module');

function password_menu( $arg ){
print "<a href='".esc_url( add_query_arg( 'module', 'password', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='password') { print " active";}
print "'>".__( 'Modify the password', 'doliconnect')."</a>";
}
add_action( 'user_doliconnect_menu', 'password_menu', 3, 1);

function password_module( $url ){
global $current_user;
 
print dolipasswordform($current_user, $url);

}
add_action( 'user_doliconnect_password', 'password_module');

add_action( 'user_doliconnect_menu', 'paymentmethods_menu', 4, 1);
add_action( 'user_doliconnect_paymentmethods', 'paymentmethods_module');

function dolipaymentmodes_lock() {
return apply_filters( 'doliconnect_paymentmethods_lock', null);
}

function paymentmethods_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'paymentmethods', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='paymentmethods') { print " active";}
print "'>".__( 'Manage payment methods', 'doliconnect')."</a>";
}

function paymentmethods_module( $url ) {
global $wpdb, $current_user;

$request = "/doliconnector/".doliconnector($current_user, 'fk_soc')."/paymentmethods";

if ( isset($_POST['default_paymentmethod']) ) {

$data = [
'default' => 1
];

$gateway = callDoliApi("PUT", $request."/".sanitize_text_field($_POST['default_paymentmethod']), $data, dolidelay( 0, true));
$gateway = callDoliApi("GET", $request, null, dolidelay('paymentmethods', true));
print dolialert ('success', __( 'You changed your default payment method', 'doliconnect'));
} elseif ( isset($_POST['delete_paymentmethod']) ) {

$gateway = callDoliApi("DELETE", $request."/".sanitize_text_field($_POST['delete_paymentmethod']), null, dolidelay( 0, true));
$gateway = callDoliApi("GET", $request, null, dolidelay('paymentmethods', true));
print dolialert ('success', __( 'You deleted a payment method', 'doliconnect'));
} elseif ( isset($_POST['add_paymentmethod']) ) {

$data = [
'default' => isset($_POST['default'])?$_POST['default']:0,
];

$gateway = callDoliApi("POST", $request."/".sanitize_text_field($_POST['add_paymentmethod']), $data, dolidelay( 0, true));
$gateway = callDoliApi("GET", $request, null, dolidelay('paymentmethods', true));
print dolialert ('success', __( 'You added a new payment method', 'doliconnect'));
}

print dolipaymentmethods(null, null, $url, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));

}

//*****************************************************************************************

if ( !empty(doliconst('MAIN_MODULE_WISHLIST')) ) {
add_action( 'customer_doliconnect_menu', 'wishlist_menu', 0, 1);
add_action( 'customer_doliconnect_wishlist', 'wishlist_module' );
}  

function wishlist_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'wishlist', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='wishlist') { print " active";}
print "'>".__( 'Wishlist', 'doliconnect' )."</a>";
}

function wishlist_module( $url ) {
global $current_user;

$request = "/wishlist?sortfield=t.rowid&sortorder=ASC&thirdparty_ids=".doliconnector($current_user, 'fk_soc')."&sqlfilters=(t.priv%3A%3D%3A0)";
$wishlist = callDoliApi("GET", $request, null, dolidelay('product', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

if ( isset ($_POST['delete_wish']) && $_POST['delete_wish'] > 0 ) {
//$memberv = callDoliApi("GET", "/adherentsplus/".esc_attr($_POST['unlink_member']), null, 0);
//if ( $memberv->socid == doliconnector($current_user, 'fk_soc') ) {
// try deleting
$delete = callDoliApi("DELETE", "/wishlist/".esc_attr($_POST['delete_wish']), null, 0);

$msg = dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));

//} else {
// fail deleting
//}
$wishlist = callDoliApi("GET", $request, null, dolidelay('product', true));

}

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">';

$representatives = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc')."/representatives", null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
 
if ( !isset( $representatives->error ) && $representatives != null ) {
foreach ( $representatives as $representative ) { 
print "<li class='list-group-item list-group-item-light'><center>".__( 'Your representative :', 'doliconnect')." ".$representative->firstname." ".$representative->lastname."".$representative->job." ".$representative->phone." ".$representative->email."</center></li>";
}}
  
if ( !isset( $wishlist->error ) && $wishlist != null ) {
foreach ( $wishlist as $wish ) { 

print apply_filters( 'doliproductlist', $wish);
 
}
} else {
print "<li class='list-group-item list-group-item-light'><center>".__( 'No product', 'doliconnect')."</center></li>";
}
print  "</ul><div class='card-body'>";

//print "<button type='submit' name='dolicart' value='validation' class='btn btn-warning w-100' role='button' aria-pressed='true'><b>".__( 'Order', 'doliconnect-wishlist')."</b></button>";

print "</div>";

print '<div class="card-footer text-muted">';
print "<small><div class='float-left'>";
print dolirefresh( $request, $url, dolidelay('product'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}

if ( !empty(doliconst('MAIN_MODULE_PROPALE')) ) {
add_action( 'customer_doliconnect_menu', 'proposals_menu', 1, 1);
add_action( 'customer_doliconnect_proposals', 'proposals_module');
}

function proposals_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'proposals', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ( $arg == 'proposals' ) { print " active";}
print "'>".__( 'Propals tracking', 'doliconnect')."</a>";
}

function proposals_module( $url ) {
global $current_user;

if ( isset($_GET['id']) && $_GET['id'] > 0 ) {

$request = "/proposals/".esc_attr($_GET['id'])."?contact_list=0";

$proposalfo = callDoliApi("GET", $request, null, dolidelay('proposal', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $proposalfo;
}

if ( !isset($proposalfo->error) && isset($_GET['id']) && isset($_GET['ref']) && ( doliconnector($current_user, 'fk_soc') == $proposalfo->socid ) && ( $_GET['ref'] == $proposalfo->ref ) && $proposalfo->statut != 0 && isset($_GET['security']) && wp_verify_nonce( $_GET['security'], 'doli-proposals-'.$proposalfo->id.'-'.$proposalfo->ref)) {
print "<div class='card shadow-sm'><div class='card-body'><h5 class='card-title'>$proposalfo->ref</h5><div class='row'><div class='col-md-5'>";
$datevalidation =  wp_date('d/m/Y', $proposalfo->date_validation);
print "<b>".__( 'Date of creation', 'doliconnect').":</b> ".wp_date('d/m/Y', $proposalfo->date_creation)."<br>";
print "<b>".__( 'Validation', 'doliconnect')." : </b> $datevalidation<br>";
//print "<b>Date de fin de validité:</b> $datevalidite";
//print "<b>".__( 'Status', 'doliconnect')." : </b> ";
if ( $proposalfo->statut == 3 ) { $propalinfo=__( 'Refused', 'doliconnect');
$propalavancement=0; }
elseif ( $proposalfo->statut == 2 ) { $propalinfo=__( 'Processing', 'doliconnect');
$propalavancement=65; }
elseif ( $proposalfo->statut == 1 ) { $propalinfo=__( 'Sign before', 'doliconnect')." ".wp_date('d/m/Y', $proposalfo->fin_validite);
$propalavancement=42; }
elseif ( $proposalfo->statut == 0 ) { $propalinfo=__( 'Processing', 'doliconnect');
$propalavancement=22; }
elseif ( $proposalfo->statut == -1 ) { $propalinfo=__( 'Canceled', 'doliconnect');
$propalavancement=0; }
print "<br><br>";
//print "<b>Moyen de paiement : </b> $proposalfo[mode_reglement]<br>";
print "</div><div class='col-md-7'>";

if ( isset($propalinfo) ) {
print "<h3 class='text-right'>".$propalinfo."</h3>";
}

$TTC = number_format($proposalfo->multicurrency_total_ttc, 2, ',', ' ');
$currency = strtolower($proposalfo->multicurrency_code);
print "</div></div>";

print '<div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '.$propalavancement.'%" aria-valuenow="'.$propalavancement.'" aria-valuemin="0" aria-valuemax="100"></div></div>';
print "<div class='w-auto text-muted d-none d-sm-block' ><div style='display:inline-block;width:16%'>".__( 'Propal', 'doliconnect')."</div><div style='display:inline-block;width:21%'>".__( 'Processing', 'doliconnect')."</div><div style='display:inline-block;width:19%'>".__( 'Validation', 'doliconnect')."</div><div style='display:inline-block;width:24%'>".__( 'Processing', 'doliconnect')."</div><div class='text-right' style='display:inline-block;width:20%'>".__( 'Billing', 'doliconnect')."</div></div>";

print "</div><ul class='list-group list-group-flush'>";
 
print doliline($proposalfo, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));

print dolitotal($proposalfo);

if ( $proposalfo->last_main_doc != null ) {
$doc = array_reverse( explode("/", $proposalfo->last_main_doc) );      
$document = dolidocdownload($doc[2], $doc[1], $doc[0], __( 'Summary', 'doliconnect'));
} 
    
$fruits[$proposalfo->date_creation.'p'] = array(
"timestamp" => $proposalfo->date_creation,
"type" => __( 'Propal', 'doliconnect'),  
"label" => $proposalfo->ref,
"document" => $document,
"description" => null,
);

sort($fruits, SORT_NUMERIC | SORT_FLAG_CASE);
foreach ( $fruits as $key => $val ) {
print "<li class='list-group-item'><div class='row'><div class='col-6 col-md-3'>" . wp_date('d/m/Y H:i', $val['timestamp']) . "</div><div class='col-6 col-md-2'>" . $val['type'] . "</div>";
print "<div class='col-md-7'><h6>" . $val['label'] . "</h6>" . $val['description'] ."" . $val['document'] ."</div></div></li>";
} 
//var_dump($fruits);
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('proposal'), $proposalfo);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

} else {

$request = "/proposals?sortfield=t.rowid&sortorder=ASC&limit=8&thirdparty_ids=".doliconnector($current_user, 'fk_soc')."&sqlfilters=(t.fk_statut!=0)";

if ( isset($_GET['pg']) ) { $page="&page=".$_GET['pg']; }

$listpropal = callDoliApi("GET", $request, null, dolidelay('proposal', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">';  
if ( !isset( $listpropal->error ) && $listpropal != null ) {
foreach ( $listpropal as $postproposal ) { 
$nonce = wp_create_nonce( 'doli-proposals-'. $postproposal->id.'-'.$postproposal->ref);
$arr_params = array( 'id' => $postproposal->id, 'ref' => $postproposal->ref, 'security' => $nonce);  
$return = esc_url( add_query_arg( $arr_params, $url) );
                
print "<a href='$return' class='list-group-item d-flex justify-content-between lh-condensed list-group-item-light list-group-item-action'><div><i class='fa fa-shopping-bag fa-3x fa-fw'></i></div><div><h6 class='my-0'>".$postproposal->ref."</h6><small class='text-muted'>du ".wp_date('d/m/Y', $postproposal->date_creation)."</small></div><span>".doliprice($postproposal, 'ttc', isset($postproposal->multicurrency_code) ? $postproposal->multicurrency_code : null)."</span><span>";
if ( $postproposal->statut == 3 ) {
if ( $postproposal->billed == 1 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-success'></span><span class='fa fa-file-text fa-fw text-success'></span>"; } 
else { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-success'></span><span class='fa fa-file-text fa-fw text-warning'></span>"; } }
elseif ( $postproposal->statut == 2 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-warning'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postproposal->statut == 1 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-warning'></span><span class='fa fa-truck fa-fw text-danger'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postproposal->statut == 0 ) { print "<span class='fa fa-check-circle fa-fw text-warning'></span><span class='fa fa-eur fa-fw text-danger'></span><span class='fa fa-truck fa-fw text-danger'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postproposal->statut == -1 ) { print "<span class='fa fa-check-circle fa-fw text-secondary'></span><span class='fa fa-eur fa-fw text-secondary'></span><span class='fa fa-truck fa-fw text-secondary'></span><span class='fa fa-file-text fa-fw text-secondary'></span>"; }
print "</span></a>";
}}
else{
print "<li class='list-group-item list-group-item-light'><center>".__( 'No proposal', 'doliconnect')."</center></li>";
}
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('proposal'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}
}

if ( !empty(doliconst('MAIN_MODULE_COMMANDE')) ) {
add_action( 'customer_doliconnect_menu', 'orders_menu', 2, 1);
add_action( 'customer_doliconnect_orders', 'orders_module');
}

function orders_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'orders', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ( $arg == 'orders' ) { print " active"; }
print "'>".__( 'Orders tracking', 'doliconnect')."</a>";
}

function orders_module( $url ) {
global $current_user;

if ( isset($_GET['id']) && $_GET['id'] > 0 ) { 

$request = "/orders/".esc_attr($_GET['id'])."?contact_list=0";

$orderfo = callDoliApi("GET", $request, null, dolidelay('order', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $orderfo;
}

if ( !isset($orderfo->error) && isset($_GET['id']) && isset($_GET['ref']) && (doliconnector($current_user, 'fk_soc') == $orderfo->socid ) && ($_GET['ref'] == $orderfo->ref) && $orderfo->statut != 0 && isset($_GET['security']) && wp_verify_nonce( $_GET['security'], 'doli-orders-'.$orderfo->id.'-'.$orderfo->ref)) {

print "<div class='card shadow-sm'><div class='card-body'><h5 class='card-title'>$orderfo->ref</h5><div class='row'><div class='col-md-6'>";
print "<b>".__( 'Date of order', 'doliconnect').":</b> ".wp_date('d/m/Y', $orderfo->date_creation)."<br>";
if ( $orderfo->statut > 0 ) {
if ( $orderfo->billed == 1 ) {
if ( $orderfo->statut > 1 ) { $orderinfo=__( 'Shipped', 'doliconnect'); 
$orderavancement=100; }
else { $orderinfo=__( 'Processing', 'doliconnect');
$orderavancement=40; }
} else { $orderinfo=null;
$orderinfo=null;
$orderavancement=25;
$orderinfo=__( 'Validated', 'doliconnect');
}
}
elseif ( $orderfo->statut == 0 ) { $orderinfo=__( 'Draft', 'doliconnect');
$orderavancement=7; }
elseif ( $orderfo->statut == -1 ) { $orderinfo=__( 'Canceled', 'doliconnect');
$orderavancement=0;  }

$mode_reglement = callDoliApi("GET", "/setup/dictionary/payment_types?sortfield=code&sortorder=ASC&limit=100&active=1&sqlfilters=(t.code%3A%3D%3A'".$orderfo->mode_reglement_code."')", null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
if (!empty($orderfo->mode_reglement_id)) print "<b>".__( 'Payment method', 'doliconnect').":</b> ".$mode_reglement[0]->label."<br>";
if (!empty($orderfo->cond_reglement_id)) print "<b>".__( 'Payment term', 'doliconnect').":</b> ".dolipaymentterm($orderfo->cond_reglement_id)."<br>";

print "<br></div><div class='col-md-6'>";

if ( isset($orderinfo) ) {
print "<h3 class='text-right'>".$orderinfo."</h3>";
}
print "</div>";
 
if ( $orderfo->billed != 1 && $orderfo->statut > 0 ) {
$nonce = wp_create_nonce( 'valid_dolicart-'.$orderfo->id );
$arr_params = array( 'cart' => $nonce, 'step' => 'payment', 'module' => $_GET["module"], 'id' => $orderfo->id,'ref' => $orderfo->ref);  
$return = add_query_arg( $arr_params, doliconnecturl('dolicart'));
if ( $orderfo->mode_reglement_code == 'CHQ' ) {

$listpaymentmethods = callDoliApi("GET", "/doliconnector/".doliconnector($current_user, 'fk_soc')."/paymentmethods?type=order&rowid=".$orderfo->id, null, dolidelay('paymentmethods', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print "<div class='col'><div class='card bg-light'><div class='card-body'><p align='justify'>".sprintf( __( 'Please send your cheque in the amount of <b>%1$s</b> with reference <b>%2$s</b> to <b>%3$s</b> at the following address', 'doliconnect'), doliprice($orderfo, 'ttc', isset($orderfo->multicurrency_code) ? $orderfo->multicurrency_code : null), $orderfo->ref, $listpaymentmethods->CHQ->proprio).":</p>";                                                                                                                                                                                                                                                                                                                                      
print "<p><b>".$listpaymentmethods->CHQ->owner_address."</b></p><button class='btn btn-link btn-sm' onclick='ValidDoliCart(\"".wp_create_nonce( 'valid_dolicart-'.$orderfo->id )."\")' id='button-source-payment'><small><span class='fas fa-sync-alt'></span> ".__( 'Change your payment mode', 'doliconnect')."</small></button></div></div></div>";
} elseif ( $orderfo->mode_reglement_code == 'VIR' ) { 

$listpaymentmethods = callDoliApi("GET", "/doliconnector/".doliconnector($current_user, 'fk_soc')."/paymentmethods", null, dolidelay('paymentmethods', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print "<div class='col'><div class='card bg-light'><div class='card-body'><p align='justify'>".sprintf( __( 'Please send your transfert in the amount of <b>%1$s</b> with reference <b>%2$s</b> at the following account', 'doliconnect'), doliprice($orderfo, 'ttc', isset($orderfo->multicurrency_code) ? $orderfo->multicurrency_code : null), $orderfo->ref ).":";
print "<br><b>".__( 'Bank', 'doliconnect').": ".$listpaymentmethods->VIR->bank."</b>";
print "<br><b>IBAN: ".$listpaymentmethods->VIR->iban."</b>";
if ( ! empty($listpaymentmethods->VIR->bic) ) { print "<br><b>BIC/SWIFT : ".$listpaymentmethods->VIR->bic."</b>";}
print "</p><button class='btn btn-link btn-sm' onclick='ValidDoliCart(\"".wp_create_nonce( 'valid_dolicart-'.$orderfo->id )."\")' id='button-source-payment'><small><span class='fas fa-sync-alt'></span> ".__( 'Change your payment mode', 'doliconnect')."</small></button></div></div></div>";
} elseif ( $orderfo->mode_reglement_code == 'PRE' ) { 

} else {
print "<button type='button' onclick='ValidDoliCart(\"".wp_create_nonce( 'valid_dolicart-'.$orderfo->id )."\")' id='button-source-payment' class='btn btn-warning btn-block' ><span class='fa fa-credit-card'></span> ".__( 'Pay', 'doliconnect')."</button>";
}
print "<script>";
print "function ValidDoliCart(nonce) {
jQuery('#DoliconnectLoadingModal').modal('show');
var form = document.createElement('form');
form.setAttribute('action', '".$return."');
form.setAttribute('method', 'post');
form.setAttribute('id', 'doliconnect-cartform');
var inputvar = document.createElement('input');
inputvar.setAttribute('type', 'hidden');
inputvar.setAttribute('name', 'dolichecknonce');
inputvar.setAttribute('value', nonce);
form.appendChild(inputvar);
document.body.appendChild(form);
form.submit();
        }";                  
print "</script>";
}

print "</div><br>"; 

$thirdparty = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc'), null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print "<div class='card-group'>"; 
if (!empty($orderfo->contacts_ids) && is_array($orderfo->contacts_ids)) {

foreach ($orderfo->contacts_ids as $contact) {
if ('BILLING' == $contact->code) {
$billingcard = dolicontact($contact->id, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
}
if ('SHIPPING' == $contact->code) {
$shippingcard = dolicontact($contact->id, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
}
}
print "<div class='card card bg-light'><div class='card-body'><h6>".__( 'Billing address', 'doliconnect')."</h6><small class='text-muted'>";
if (isset($billingcard) && !empty($billingcard)) {
print $billingcard;
} else {
print doliaddress($thirdparty, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
}
print "</small></div></div>";
print "<div class='card card bg-light'><div class='card-body'><h6>".__( 'Shipping address', 'doliconnect')."</h6><small class='text-muted'>";
if (isset($shippingcard) && !empty($shippingcard)) {
print $shippingcard;
} else {
print doliaddress($thirdparty, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
}
print "</small></div></div>";
} else {
print "<div class='card card bg-light'><div class='card-body'><h6>".__( 'Billing and shipping address', 'doliconnect')."</h6><small class='text-muted'>";
print doliaddress($thirdparty, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));
print "</small></div></div>";
}
print "</div><br>";

print '<div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '.$orderavancement.'%" aria-valuenow="'.$orderavancement.'" aria-valuemin="0" aria-valuemax="100"></div></div>';
print "<div class='w-auto text-muted d-none d-sm-block' ><div style='display:inline-block;width:20%'>".__( 'Order', 'doliconnect')."</div><div style='display:inline-block;width:15%'>".__( 'Payment', 'doliconnect')."</div><div style='display:inline-block;width:25%'>".__( 'Processing', 'doliconnect')."</div><div style='display:inline-block;width:20%'>".__( 'Shipping', 'doliconnect')."</div><div class='text-right' style='display:inline-block;width:20%'>".__( 'Delivery', 'doliconnect')."</div></div>";

print "</div><ul class='list-group list-group-flush'>";
 
print doliline($orderfo, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));

print dolitotal($orderfo);

if ( $orderfo->last_main_doc != null ) {
$doc = array_reverse(explode("/", $orderfo->last_main_doc)); 
$document_order = dolidocdownload($doc[2], $doc[1], $doc[0], __( 'Summary', 'doliconnect'));
} else {
$document_order = dolidocdownload('order', $orderfo->ref, $orderfo->ref.'.pdf', __( 'Summary', 'doliconnect'), true);
} 
    
$fruits[$orderfo->date_commande.'o'] = array(
"timestamp" => $orderfo->date_creation,
"type" => __( 'Order', 'doliconnect'),  
"label" => $orderfo->ref,
"document" => $document_order,
"description" => null,
);

if ( isset($orderfo->linkedObjectsIds->facture) && $orderfo->linkedObjectsIds->facture != null ) {
foreach ($orderfo->linkedObjectsIds->facture as $f => $value) {

if ($value > 0) {
$invoice = callDoliApi("GET", "/invoices/".$value."?contact_list=0", null, dolidelay('order', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $invoice;
$payment = callDoliApi("GET", "/invoices/".$value."/payments", null, dolidelay('order', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $payment;
}

if ( $payment != null ) { 
foreach ( $payment as $pay ) {
$fruits[strtotime($pay->date).'p'] = array(
"timestamp" => strtotime($pay->date),
"type" => __( 'Payment', 'doliconnect'),  
"label" => "$pay->type de ".doliprice($pay->amount, isset($orderfo->multicurrency_code) ? $orderfo->multicurrency_code : null),
"description" => $pay->num,
"document" => null,
); 
}
}

if ( $invoice->last_main_doc != null ) {
$doc = array_reverse(explode("/", $invoice->last_main_doc)); 
$document_invoice = dolidocdownload($doc[2], $doc[1], $doc[0], __( 'Invoice', 'doliconnect'));
} else {
$document_invoice = dolidocdownload('invoice', $invoice->ref, $invoice->ref.'.pdf', __( 'Invoice', 'doliconnect'), true);
}

if ( $invoice->paye != 1 && $invoice->remaintopay != 0 && function_exists('dolipaymentmodes') ) {

$payment_invoice = "<a href='".doliconnecturl('dolicart')."?pay&module=invoices&id=".$invoice->id."&ref=".$invoice->ref."' id='button-source-payment' class='btn btn-warning btn-block' role='button'><span class='fa fa-credit-card'></span> ".__( 'Pay', 'doliconnect')."</a><br>";

} elseif ( $invoice->paye != 1 && $invoice->remaintopay != 0 &&  isset($orderfo->public_payment_url) && !empty($orderfo->public_payment_url) ) {

$payment_invoice = "<a href='".$orderfo->public_payment_url."' id='button-source-payment' class='btn btn-warning btn-block' role='button'><span class='fa fa-credit-card'></span> ".__( 'Pay', 'doliconnect')."</a><br>";

} else {
$payment_invoice = null;
}
  
$fruits[$invoice->date_creation.'i'] = array(
"timestamp" => $invoice->date_creation,
"type" => __( 'Invoice', 'doliconnect'),  
"label" => $invoice->ref,
"document" => $document_invoice,
"description" => $payment_invoice,
);  
} 
} 
 
if ( isset($orderfo->linkedObjectsIds->shipping) ) {
foreach ( $orderfo->linkedObjectsIds->shipping as $s => $value ) {

if ($value > 0) {
$ship = callDoliApi("GET", "/shipments/".$value, null, dolidelay('order', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print var_dump($ship);
}

$lnship ="<ul>";
foreach ( $ship->lines as $sline ) {
$lnship .="<li>".$sline->qty_shipped."/".$sline->qty_shipped." ".$sline->libelle."</li>";
}
$lnship .="</ul>";
if ( $ship->trueWeight != null ) {
$poids = " ".__( 'of', 'doliconnect')." ".$ship->trueWeight." ".doliunit($ship->weight_units, 'weight');
} else { $poids = ''; }
if ( $ship->trueSize != null && $ship->trueSize != 'xx' ) {
$dimensions = " - ".__( 'size', 'doliconnect')." ".$ship->trueSize." ".doliunit($ship->size_units, 'size');
} else  { $dimensions = ''; }
if ( $ship->statut > 0 ) {
if ( !empty($ship->date_delivery) ) {
$datedelivery = "<br>".__( 'Estimated delivery', 'doliconnect').": ".wp_date( get_option( 'date_format' ), $ship->date_delivery, false);
} else { $datedelivery = ''; }
$fruits[$ship->date_creation] = array(
"timestamp" => $ship->date_creation,
"type" => __( 'Shipment', 'doliconnect'),  
"label" => $ship->ref." ".$ship->tracking_url.$datedelivery,
"description" => "<small>".$lnship.__( 'Parcel', 'doliconnect')." ".$ship->shipping_method.$poids.$dimensions."</small>",
"document" => null,
);
} else {
$fruits[$ship->date_creation] = array(
"timestamp" => $ship->date_creation,
"type" => __( 'Shipment', 'doliconnect'),  
"label" => __( 'Packaging in progress', 'doliconnect'),
"description" => null,
"document" => null,
);
}
 } 
 }

sort($fruits, SORT_NUMERIC | SORT_FLAG_CASE);
foreach ( $fruits as $key => $val ) {
print "<li class='list-group-item'><div class='row'><div class='col-6 col-md-3'>" . wp_date('d/m/Y H:i', $val['timestamp']) . "</div><div class='col-6 col-md-2'>" . $val['type'] . "</div>";
print "<div class='col-md-7'><h6>".$val['label']."</h6>" . $val['description'] ."" . $val['document'] ."</div></div></li>";
} 
//var_dump($fruits);
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('order'), $orderfo);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

} else {

$request= "/orders?sortfield=t.rowid&sortorder=DESC&limit=8&thirdparty_ids=".doliconnector($current_user, 'fk_soc')."&sqlfilters=(t.fk_statut!=0)"; //".$page."

if ( isset($_GET['pg']) && $_GET['pg'] > 0) { $page="&page=".$_GET['pg'];}  else { $page=""; }

$listorder = callDoliApi("GET", $request, null, dolidelay('order', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">';
if ( !isset($listorder->error ) && $listorder != null ) {
foreach ( $listorder as $postorder ) {
$nonce = wp_create_nonce( 'doli-orders-'. $postorder->id.'-'.$postorder->ref);
$arr_params = array( 'id' => $postorder->id, 'ref' => $postorder->ref, 'security' => $nonce);  
$return = esc_url( add_query_arg( $arr_params, $url) );
                                                                                                                                                      
print "<a href='$return' class='list-group-item d-flex justify-content-between lh-condensed list-group-item-light list-group-item-action'><div><i class='fa fa-file-invoice fa-3x fa-fw'></i></div><div><h6 class='my-0'>".$postorder->ref."</h6><small class='text-muted'>du ".wp_date('d/m/Y', $postorder->date_commande)."</small></div><span>".doliprice($postorder, 'ttc', isset($postorder->multicurrency_code) ? $postorder->multicurrency_code : null)."</span><span>";
if ( $postorder->statut > 0 ) { print "<span class='fas fa-check-circle fa-fw text-success'></span> ";
if ( $postorder->billed == 1 ) { print "<span class='fas fa-money-bill-alt fa-fw text-success'></span> "; 
if ( $postorder->statut > 1 ) { print "<span class='fas fa-dolly fa-fw text-success'></span> "; }
else { print "<span class='fas fa-dolly fa-fw text-warning'></span> "; }
}
else { print "<span class='fas fa-money-bill-alt fa-fw text-warning'></span> "; 
if ( $postorder->statut > 1 ) { print "<span class='fas fa-dolly fa-fw text-success'></span> "; }
else { print "<span class='fas fa-dolly fa-fw text-danger'></span> "; }
}}
elseif ( $postorder->statut == 0 ) { print "<span class='fas fa-check-circle fa-fw text-warning'></span> <span class='fas fa-money-bill-alt fa-fw text-danger'></span> <span class='fas fa-dolly fa-fw text-danger'></span>"; }
elseif ( $postorder->statut == -1 ) { print "<span class='fas fa-check-circle fa-fw text-secondary'></span> <span class='fas fa-money-bill-alt fa-fw text-secondary'></span> <span class='fas fa-dolly fa-fw text-secondary'></span>"; }
print "</span></a>";
}}
else{
print "<li class='list-group-item list-group-item-light'><center>".__( 'No order', 'doliconnect')."</center></li>";
}
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('order'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

//print '<br><nav aria-label="Page navigation example">
//  <ul class="pagination">
//  <li class="page-item disabled">
//      <a class="page-link" href="#" aria-label="Previous">
//        <span aria-hidden="true">&laquo;</span>
//        <span class="sr-only">Previous</span>
//     </a>
//  </li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=1">1</a></li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=2">3</a></li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=3">3</a></li>    
//  <li class="page-item disabled">
//      <a class="page-link" href="#" aria-label="Next">
//        <span aria-hidden="true">&raquo;</span>
//        <span class="sr-only">Next</span>
//      </a>
//  </li>
//  </ul>
//</nav>';
}
}

if ( !empty(doliconst('MAIN_MODULE_CONTRAT'))  && !empty(get_option('doliconnectbeta')) ) {
add_action( 'customer_doliconnect_menu', 'contracts_menu', 2, 1);
add_action( 'customer_doliconnect_contracts', 'contracts_module');
}

function contracts_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'contracts', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ( $arg == 'contracts' ) { print " active"; }
print "'>".__( 'Contracts tracking', 'doliconnect')."</a>";
}

function contracts_module( $url ) {
global $current_user;

if ( isset($_GET['id']) && $_GET['id'] > 0 ) {  

$request = "/contracts/".esc_attr($_GET['id'])."?contact_list=0";

$contractfo = callDoliApi("GET", $request, null, dolidelay('contract', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $contractfo;
}

if ( !isset($contractfo->error) && isset($_GET['id']) && isset($_GET['id']) && isset($_GET['ref']) && (doliconnector($current_user, 'fk_soc') == $contractfo->socid) && ($_GET['ref'] == $contractfo->ref) && isset($_GET['security']) && wp_verify_nonce( $_GET['security'], 'doli-contracts-'.$contractfo->id.'-'.$contractfo->ref)) {
print "<div class='card shadow-sm'><div class='card-body'><h5 class='card-title'>$contractfo->ref</h5><div class='row'><div class='col-md-5'>";
print "<b>".__( 'Date of creation', 'doliconnect').": </b> ".wp_date('d/m/Y', $contractfo->date_creation)."<br>";
if ( $contractfo->statut > 0 ) {
//if ( $contractfo->billed == 1 ) {
//if ( $contractfo->statut > 1 ) { $contractfo=__( 'Shipped', 'doliconnect'); 
//$orderavancement=100; }
//else { $orderinfo=__( 'Processing', 'doliconnect');
//$contractavancement=40; }
//}
//else { $contractinfo=null;
//$contractinfo=null;
//$contractavancement=25;
//}
$contractavancement=0; 
}
elseif ( $contractfo->statut == 0 ) { $contractinfo=__( 'Validation', 'doliconnect');
$contractavancement=7; }
elseif ( $contractfo->statut == -1) { $contractinfo=__( 'Canceled', 'doliconnect');
$contractavancement=0; }

print "</div></div>";

print '<div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '.$contractavancement.'%" aria-valuenow="'.$contractavancement.'" aria-valuemin="0" aria-valuemax="100"></div></div>';
print "<div class='w-auto text-muted d-none d-sm-block' ><div style='display:inline-block;width:20%'>".__( 'Order', 'doliconnect')."</div><div style='display:inline-block;width:15%'>".__( 'Payment', 'doliconnect')."</div><div style='display:inline-block;width:25%'>".__( 'Processing', 'doliconnect')."</div><div style='display:inline-block;width:20%'>".__( 'Shipping', 'doliconnect')."</div><div class='text-right' style='display:inline-block;width:20%'>".__( 'Delivery', 'doliconnect')."</div></div>";

print "</div><ul class='list-group list-group-flush'>";

print doliline($contractfo, esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null));

print dolitotal($contractfo);

if ( $contractfo->last_main_doc != null ) {
$doc = array_reverse( explode("/", $contractfo->last_main_doc) );      
$document = dolidocdownload($doc[2], $doc[1], $doc[0], __( 'Summary', 'doliconnect'));
} 
    
$fruits[$contractfo->date_creation.'p'] = array(
"timestamp" => $contractfo->date_creation,
"type" => __( 'contract', 'doliconnect'),  
"label" => $contractfo->ref,
"document" => "",
"description" => null,
);

sort($fruits, SORT_NUMERIC | SORT_FLAG_CASE);
foreach ( $fruits as $key => $val ) {
print "<li class='list-group-item'><div class='row'><div class='col-6 col-md-3'>" . wp_date('d/m/Y H:i', $val['timestamp']) . "</div><div class='col-6 col-md-2'>" . $val['type'] . "</div>";
print "<div class='col-md-7'><h6>" . $val['label'] . "</h6>" . $val['description'] ."" . $val['document'] ."</div></div></li>";
} 

//var_dump($fruits);
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('contract'), $contractfo);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

} else {

$request = "/contracts?sortfield=t.rowid&sortorder=DESC&limit=8&thirdparty_ids=".doliconnector($current_user, 'fk_soc'); //".$page."

if ( isset($_GET['pg']) && $_GET['pg'] ) { $page="&page=".$_GET['pg'];} else { $page=""; }
                                 
$listcontract = callDoliApi("GET", $request, null, dolidelay('contract', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">';
if ( !isset($listcontract->error) && $listcontract != null ) {
foreach ($listcontract  as $postcontract) {                                                                                 
$nonce = wp_create_nonce( 'doli-contracts-'. $postcontract->id.'-'.$postcontract->ref);
$arr_params = array( 'id' => $postcontract->id, 'ref' => $postcontract->ref, 'security' => $nonce);  
$return = esc_url( add_query_arg( $arr_params, $url) );
                                                                                                                                                      
print "<a href='$return' class='list-group-item d-flex justify-content-between lh-condensed list-group-item-light list-group-item-action'><div><i class='fa fa-file-contract fa-3x fa-fw'></i></div><div><h6 class='my-0'>".$postcontract->ref."</h6><small class='text-muted'>du ".wp_date('d/m/Y', $postcontract->date_creation)."</small></div><span>".doliprice($postcontract, 'ttc', isset($postcontract->multicurrency_code) ? $postcontract->multicurrency_code : null)."</span><span>";
if ( $postcontract->statut > 0 ) {print "<span class='fas fa-check-circle fa-fw text-success'></span> ";
//if ( $postcontract->billed == 1 ) { print "<span class='fas fa-money-bill-alt fa-fw text-success'></span> "; 
//if ( $postcontract->statut > 1 ) { print "<span class='fas fa-shipping-fast fa-fw text-success'></span> "; }
//else { print "<span class='fas fa-shipping-fast fa-fw text-warning'></span> "; }
//}
//else { print "<span class='fas fa-money-bill-alt fa-fw text-warning'></span> "; 
//if ( $postcontract->statut > 1 ) { print "<span class='fas fa-shipping-fast fa-fw text-success'></span> "; }
//else { print "<span class='fas fa-shipping-fast fa-fw text-danger'></span> "; }
//}
}
elseif ( $postcontract->statut == 0 ) { print "<span class='fas fa-check-circle fa-fw text-warning'></span> <span class='fas fa-money-bill-alt fa-fw text-danger'></span> <span class='fas fa-shipping-fast fa-fw text-danger'></span>";}
elseif ( $postcontract->statut == -1 ) {print "<span class='fas fa-check-circle fa-fw text-secondary'></span> <span class='fas fa-money-bill-alt fa-fw text-secondary'></span> <span class='fas fa-shipping-fast fa-fw text-secondary'></span>";}
print "</span></a>";
}}
else{
print "<li class='list-group-item list-group-item-light'><center>".__( 'No contract', 'doliconnect')."</center></li>";
}
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('contract'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

//print '<br><nav aria-label="Page navigation example">
//  <ul class="pagination">
//    <li class="page-item disabled">
//      <a class="page-link" href="#" aria-label="Previous">
//        <span aria-hidden="true">&laquo;</span>
//        <span class="sr-only">Previous</span>
//     </a>
 //   </li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=1">1</a></li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=2">3</a></li>
//    <li class="page-item"><a class="page-link" href="'.$url.'&pg=3">3</a></li>    
//    <li class="page-item disabled">
//      <a class="page-link" href="#" aria-label="Next">
//        <span aria-hidden="true">&raquo;</span>
//        <span class="sr-only">Next</span>
//      </a>
//    </li>
//  </ul>
//</nav>';

}
}

if ( !empty(doliconst('MAIN_MODULE_DON')) ) {
add_action( 'customer_doliconnect_menu', 'donations_menu', 5, 1);
add_action( 'customer_doliconnect_donations', 'donations_module');
}  

function donations_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'donations', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='donations') { print " active";}
print "'>".__( 'Donations tracking', 'doliconnect')."</a>";
}

function donations_module( $url ) {
global $wpdb, $current_user;
$entity = get_current_blog_id();
$ID = $current_user->ID;

if ( isset($_GET['id']) && $_GET['id'] > 0 ) { 
 
$request = "/donations/".esc_attr($_GET['id']);

$donationfo = callDoliApi("GET", $request, null, dolidelay('donation', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $donationfo;
}

if ( !isset($donationfo->error) && isset($_GET['id']) && isset($_GET['ref']) && (doliconnector($current_user, 'fk_soc') == $donationfo->socid ) && ($_GET['ref'] == $donationfo->ref) && $donationfo->statut != 0 ) {

print "<div class='card shadow-sm'><div class='card-body'><h5 class='card-title'>$donationfo->ref</h5><div class='row'><div class='col-md-5'>";
$datecommande =  wp_date('d/m/Y', $donationfo->date_creation);
print "<b>".__( 'Date of order', 'doliconnect').":</b> $datecommande<br>";

print "<b>".__( 'Payment method', 'doliconnect').":</b> ".__( $donationfo->mode_reglement, 'doliconnect')."<br><br></div><div class='col-md-7'>";

if ( isset($orderinfo) ) {
print "<h3 class='text-right'>".$orderinfo."</h3>";
}

$orderavancement=100;

print "</div></div>";
print '<div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '.$orderavancement.'%" aria-valuenow="'.$orderavancement.'" aria-valuemin="0" aria-valuemax="100"></div></div>';
print "<div class='w-auto text-muted d-none d-sm-block' ><div style='display:inline-block;width:20%'>".__( 'Order', 'doliconnect')."</div><div style='display:inline-block;width:15%'>".__( 'Payment', 'doliconnect')."</div><div style='display:inline-block;width:25%'>".__( 'Processing', 'doliconnect')."</div><div style='display:inline-block;width:20%'>".__( 'Shipping', 'doliconnect')."</div><div class='text-right' style='display:inline-block;width:20%'>".__( 'Delivery', 'doliconnect')."</div></div>";

print "</div><ul class='list-group list-group-flush'>";
 
if ( $donationfo->lines != null ) {
foreach ( $donationfo->lines as $line ) {
print "<li class='list-group-item'>";     
if ( $line->date_start != '' && $line->date_end != '' )
{
$start = wp_date('d/m/Y', $line->date_start);
$end = wp_date('d/m/Y', $line->date_end);
$dates =" <i>(Du $start au $end)</i>";
}

print '<div class="w-100 justify-content-between"><div class="row"><div class="col-8 col-md-10"> 
<h6 class="mb-1">'.$line->libelle.'</h6>
<p class="mb-1">'.$line->description.'</p>
<small>'.$dates.'</small>'; 
print '</div><div class="col-4 col-md-2 text-right"><h5 class="mb-1">'.doliprice($line, 'ttc', isset($line->multicurrency_code) ? $line->multicurrency_code : null).'</h5>';
print '<h5 class="mb-1">x'.$line->qty.'</h5>'; 
print "</div></div></li>";
}
}

print "<li class='list-group-item list-group-item-info'>";
print "<b>".__( 'Amount', 'doliconnect').": ".doliprice($donationfo, 'amount', isset($donationfo->multicurrency_code) ? $donationfo->multicurrency_code : null)."</b>";
print "</li>";
print "</ul></div>";

print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('donation'), $donationfo);
print "</div><div class='float-right'>";
print dolihelp('COM');
print "</div></small>";

} else {

if ( isset($_GET['pg']) ) { $page="&page=".$_GET['pg']; }

$request= "/donations?sortfield=t.rowid&sortorder=DESC&limit=8&thirdparty_ids=".doliconnector($current_user, 'fk_soc');// ".$page."   ."&sqlfilters=(t.fk_statut!=0)"

$listdonation = callDoliApi("GET", $request, null, dolidelay('donation', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print var_dump($listdonation);

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">'; 
if ( !empty(doliconnectid('dolidonation'))) {
print '<a href="'.doliconnecturl('dolidonation').'" class="list-group-item lh-condensed list-group-item-action list-group-item-primary "><center><i class="fas fa-plus-circle"></i> '.__( 'Donate', 'doliconnect').'</center></a>';  
}
if ( !isset( $listdonation->error ) && $listdonation != null ) {
foreach ( $listdonation as $postdonation ) { 

$arr_params = array( 'id' => $postdonation->id, 'ref' => $postdonation->ref);  
$return = esc_url( add_query_arg( $arr_params, $url) );
                
print "<a href='$return' class='list-group-item d-flex justify-content-between lh-condensed list-group-item-light list-group-item-action'><div><i class='fa fa-donate fa-3x fa-fw'></i></div><div><h6 class='my-0'>".$postdonation->ref."</h6><small class='text-muted'>du ".wp_date('d/m/Y', $postdonation->date_creation)."</small></div><span>".doliprice($postdonation, 'amount', isset($postdonation->multicurrency_code) ? $postdonation->multicurrency_code : null)."</span><span>";
if ( $postdonation->statut == 3 ) {
if ( $postdonation->billed == 1 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-success'></span><span class='fa fa-file-text fa-fw text-success'></span>"; } 
else { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-success'></span><span class='fa fa-file-text fa-fw text-warning'></span>"; } }
elseif ( $postdonation->statut == 2 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-success'></span><span class='fa fa-truck fa-fw text-warning'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postdonation->statut == 1 ) { print "<span class='fa fa-check-circle fa-fw text-success'></span><span class='fa fa-eur fa-fw text-warning'></span><span class='fa fa-truck fa-fw text-danger'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postdonation->statut == 0 ) { print "<span class='fa fa-check-circle fa-fw text-warning'></span><span class='fa fa-eur fa-fw text-danger'></span><span class='fa fa-truck fa-fw text-danger'></span><span class='fa fa-file-text fa-fw text-danger'></span>"; }
elseif ( $postdonation->statut == -1 ) { print "<span class='fa fa-check-circle fa-fw text-secondary'></span><span class='fa fa-eur fa-fw text-secondary'></span><span class='fa fa-truck fa-fw text-secondary'></span><span class='fa fa-file-text fa-fw text-secondary'></span>"; }
print "</span></a>";
}}
else{
print "<li class='list-group-item list-group-item-light'><center>".__( 'No donation', 'doliconnect')."</center></li>";
}
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('donation'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}
}

//*****************************************************************************************

if ( !empty(doliconst('MAIN_MODULE_ADHERENTSPLUS')) ) {
add_action( 'options_doliconnect_menu', 'members_menu', 1, 1);
add_action( 'options_doliconnect_members', 'members_module');
}

function members_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'members', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='members') { print " active";}
print "'>".__( 'Membership', 'doliconnect')."</a>";
}

function members_module( $url ) {
global $wpdb,$current_user;

$time = current_time( 'timestamp',1);

$request = "/adherentsplus/".doliconnector($current_user, 'fk_member', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)); 

$productadhesion = doliconst("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

if ( isset($_POST["update_membership"]) && function_exists('dolimembership') ) {
$adherent = dolimembership($current_user, $_POST["update_membership"], $_POST["typeadherent"], dolidelay('member', true));
$request = "/adherentsplus/".doliconnector($current_user, 'fk_member', true); 
//if ($statut==1) {
print dolialert ('success', __( 'Your membership has been updated.', 'doliconnect'));
//}

if ( ($_POST["update_membership"]==4) && isset($_POST["cotisation"]) && doliconnector($current_user, 'fk_member') > 0 && $_POST["timestamp_start"] > 0 && $_POST["timestamp_end"] > 0 ) {

doliaddtocart($productadhesion, 1, $_POST["cotisation"], null, $_POST["timestamp_start"], $_POST["timestamp_end"], $url);
wp_redirect(esc_url(doliconnecturl('dolicart')));
exit;     
} elseif ( $_POST["update_membership"]==5 || $_POST["update_membership"]==1 ) {
$dolibarr = callDoliApi("GET", "/doliconnector/".$current_user->ID, null, 0); 
wp_redirect(esc_url($url));
exit; 
}

} 

print "<div class='card shadow-sm'><div class='card-body'><div class='row'><div class='col-12 col-md-5'>";

if ( !empty(doliconnector($current_user, 'fk_member')) && doliconnector($current_user, 'fk_member') > 0 && doliconnector($current_user, 'fk_soc') > 0 ) { 
$adherent = callDoliApi("GET", $request, null, dolidelay('member', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
}

print "<b>".__( 'Status', 'doliconnect').":</b> ";
if ( $adherent->statut == '1') {
if  ($adherent->datefin == null ) { print  "<span class='badge badge-danger'>".__( 'Waiting payment', 'doliconnect')."</span>";}
else {
if ( $adherent->datefin+86400>$time){ print  "<span class='badge badge-success'>".__( 'Active', 'doliconnect')."</span>"; } else { print  "<span class='badge badge-danger'>".__( 'Waiting payment', 'doliconnect')."</span>";}
}}
elseif ( $adherent->statut == '0' ) {
print  "<span class='badge badge-dark'>".__( 'Terminated', 'doliconnect')."</span>";}
elseif ( $adherent->statut == '-1' ) {
print  "<span class='badge badge-warning'>".__( 'Waiting validation', 'doliconnect')."</span>";}
else {print  "<span class='badge badge-dark'>".__( 'No membership', 'doliconnect')."</span>";}
print  "<br>";
$type=(! empty($adherent->type) ? $adherent->type : __( 'nothing', 'doliconnect'));
print  "<b>".__( 'Type', 'doliconnect').":</b> ".$type."<br>";
print  "<b>".__( 'End of membership', 'doliconnect').":</b> ";
if ( $adherent->datefin == null ) { print  "***";
} else { print  wp_date('d/m/Y', $adherent->datefin); }
if ( isset($adherent->license) &&  null != $adherent->license ) print "<br><b>".__( 'License', 'doliconnect').":</b> ".$adherent->license;
//print  "<br><b>".__( 'Seniority', 'doliconnect').":</b> ";
print  "<br><b>".__( 'Commitment', 'doliconnect').":</b> ";
if ( (current_time('timestamp') > $adherent->datecommitment) || null == $adherent->datecommitment ) { print  __( 'no', 'doliconnect');
} else {
$datefin =  wp_date('d/m/Y', $adherent->datecommitment);
print  "$datefin"; }

print "</div><div class='col-12 col-md-7'>";

if ( function_exists('dolimembership_modal') && !empty(doliconst('MAIN_MODULE_COMMANDE')) && !empty($productadhesion) ) {

//print doliloaderscript('doliconnect-memberform');

if ( $adherent->datefin == null && $adherent->statut == '0' ) {print  "<a href='#' id='subscribe-button2' class='btn btn text-white btn-warning btn-block' data-toggle='modal' data-target='#activatemember'><b>".__( 'Become a member', 'doliconnect')."</b></a>";
} elseif ($adherent->statut == '1') {
if ( $time > $adherent->next_subscription_renew && $adherent->datefin != null ) {
print "<button class='btn btn text-white btn-warning btn-block' data-toggle='modal' data-target='#activatemember'>".__( 'Renew my subscription', 'doliconnect')."</button>";
} elseif ( intval(86400+(!empty($adherent->datefin)?$adherent->datefin:0)) > $time ) {
print  "<button id='subscribe-button2' class='btn btn text-white btn-warning btn-block' data-toggle='modal' data-target='#activatemember'>".__( 'Modify my subscription', 'doliconnect')."</button>";
} else { print  "<button class='btn btn btn-danger btn-block' data-toggle='modal' data-target='#activatemember'>".__( 'Pay my subscription', 'doliconnect')."</button>";}
} elseif ( $adherent->statut == '0' ) {
if ( intval(86400+(!empty($adherent->datefin)?$adherent->datefin:0)) > $time ) {
print "<form id='subscription-form' action='".doliconnecturl('doliaccount')."?module=members' method='post'><input type='hidden' name='update_membership' value='4'><button id='resiliation-button' class='btn btn btn-warning btn-block' type='submit'><b>".__( 'Reactivate my subscription', 'doliconnect')."</b></button></form>";
} else {
print  "<button class='btn btn text-white btn-warning btn-block' data-toggle='modal' data-target='#activatemember'>".__( 'Renew my subscription', 'doliconnect')."</button>";
}
} elseif ( $adherent->statut == '-1' ) {
print '<div class="clearfix"><div class="spinner-border float-left" role="status">
<span class="sr-only">Loading...</span></div>'.__('Your request has been registered. You will be notified at validation.', 'doliconnect').'</div>';
} else { 

if ( doliconnector($current_user, 'fk_soc') > 0 ) {
$thirdparty = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc'), null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));  
}

if ( empty($thirdparty->address) || empty($thirdparty->zip) || empty($thirdparty->town) || empty($thirdparty->country_id) || empty($current_user->billing_type) || empty($current_user->billing_birth) || empty($current_user->user_firstname) || empty($current_user->user_lastname) || empty($current_user->user_email)) {
print "Pour adhérer, tous les champs doivent être renseignés dans vos <a href='".esc_url( get_permalink(get_option('doliaccount')))."?module=informations&return=members' class='alert-link'>".__( 'Personal informations', 'doliconnect')."</a></div><div class='col-sm-6 col-md-7'>";
} else { 
print "<button class='btn btn text-white btn-warning btn-block' data-toggle='modal' data-target='#activatemember'>".__( 'Become a member', 'doliconnect')."</button>";
}
}


if ( $adherent->datefin != null && $adherent->statut == 1 && $adherent->datefin > $adherent->next_subscription_renew && $adherent->next_subscription_renew > current_time( 'timestamp',1) ) {
print "<center><small>".sprintf(__('Renew from %s', 'doliconnect'), wp_date('d/m/Y', $adherent->next_subscription_renew))."</small></center>";
}
}

print "</div></div>";
if ($adherent->ref != $adherent->id ) { 
print "<label for='license'><small>N° de licence</small></label><div class='input-group mb-2'><div class='input-group-prepend'><div class='input-group-text'><i class='fas fa-key fa-fw'></i></div></div><input class='form-control' type='text' value='".$adherent->ref."' readonly></div>";
}
if( has_action('mydoliconnectmemberform') ) {
print do_action('mydoliconnectmemberform', $adherent);
}
print "</div><ul class='list-group list-group-flush'>";

if (doliconnector($current_user, 'fk_member') > 0) {
$listcotisation = callDoliApi("GET", "/adherentsplus/".doliconnector($current_user, 'fk_member')."/subscriptions", null, dolidelay('member', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
} 

if ( !isset($listcotisation->error) && $listcotisation != null ) { 
foreach ( $listcotisation as $cotisation ) {                                                                                 
$dated =  wp_date('d/m/Y', $cotisation->dateh);
$datef =  wp_date('d/m/Y', $cotisation->datef);
print "<li class='list-group-item'><table width='100%' border='0'><tr><td>$cotisation->label</td><td>$dated ".__( 'to', 'doliconnect')." $datef";
print "</td><td class='text-right'><b>".doliprice($cotisation->amount)."</b></td></tr></table><span></span></li>";
}
}
else { 
print "<li class='list-group-item list-group-item-light'><center>".__( 'No subscription', 'doliconnect')."</center></li>";
}
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('member'), $adherent);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}

if ( !empty(doliconst('ADHERENT_CONSUMPTION')) && !empty(get_option('doliconnectbeta')) ) {
add_action( 'options_doliconnect_menu', 'membershipconsumption_menu', 2, 1);
add_action( 'options_doliconnect_membershipconsumption', 'membershipconsumption_module');
}  

function membershipconsumption_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'membershipconsumption', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='membershipconsumption') { print " active";}
print "'>".__( 'Consumptions monitoring', 'doliconnect')."</a>";
}

function membershipconsumption_module( $url ) {
global $current_user;

$request = "/adherentsplus/".doliconnector($current_user, 'fk_member')."/consumptions";

print "<div class='card shadow-sm'><div class='card-body'>";
print "<b>".__( 'Next billing date', 'doliconnect').": </b> <br>";

print "</div><ul class='list-group list-group-flush'>";

if (doliconnector($current_user, 'fk_member') > 0) {
$listconsumption = callDoliApi("GET", $request, null, dolidelay('member', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
} 

if ( !isset($listconsumption->error) && $listconsumption != null ) { 
foreach ( $listconsumption as $consumption ) {                                                                                 
$datec =  wp_date('d/m/Y H:i', $consumption->date_creation);
print "<li class='list-group-item'><table width='100%'><tr><td>$datec</td><td>$consumption->label</td><td>";

if ( !empty($consumption->value) ) {
print $consumption->value." ".$consumption->unit;
} else {
print "x$consumption->qty";
}

print "</td>";
print "<td class='text-right'><b>".doliprice($consumption->amount)."</b></td></tr></table><span></span></li>";
}
} else { 
print "<li class='list-group-item list-group-item-light'><center>".__( 'No consumption', 'doliconnect')."</center></li>";
}

print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('member'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}

if ( !empty(doliconst('ADHERENT_LINKEDMEMBER')) ) {
add_action( 'options_doliconnect_menu', 'linkedmember_menu', 3, 1);
add_action( 'options_doliconnect_linkedmember', 'linkedmember_module');
}  

function linkedmember_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'linkedmember', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='linkedmember') { print " active";}
print "'>".__( 'Manage linked members', 'doliconnect')."</a>";
}

function linkedmember_module( $url ) {
global $current_user;

$request = "/adherentsplus/".doliconnector($current_user, 'fk_member')."/linkedmembers";

if ( isset ($_POST['unlink_member']) && $_POST['unlink_member'] > 0 ) {
//$memberv = callDoliApi("GET", "/adherentsplus/".esc_attr($_POST['unlink_member']), null, 0);
//if ( $memberv->socid == doliconnector($current_user, 'fk_soc') ) {
// try deleting
$delete = callDoliApi("DELETE", $request."/".esc_attr($_POST['unlink_member']), null, 0);

print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));

//} else {
// fail deleting
//}
$linkedmember = callDoliApi("GET", $request, null, dolidelay('member', true));

} elseif ( isset ($_POST['update_member']) && $_POST['update_member'] > 0 ) {

$memberv=$_POST['member'][''.$_POST['update_member'].''];

$memberv = callDoliApi("PUT", "/adherentsplus/".esc_attr($_POST['update_member']), $memberv, dolidelay('member', true));
if ( false === $memberv ) {
// fail deleting

} else {
print dolialert ('success', __( 'Your informations have been updated.', 'doliconnect'));
$linkedmember = callDoliApi("GET", $request, null, dolidelay('member', true));
}

} elseif (doliconnector($current_user, 'fk_member') > 0) {

$linkedmember= callDoliApi("GET", $request, null, dolidelay('member', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

}

print "<form role='form' action='$url' id='doliconnect-linkedmembersform' method='post'>";                      

print doliloaderscript('doliconnect-linkedmembersform'); 

print "<div class='card shadow-sm'><ul class='list-group list-group-flush'>";

print '<button type="button" class="list-group-item lh-condensed list-group-item-action list-group-item-primary" data-toggle="modal" data-target="#addmember"><center><i class="fas fa-plus-circle"></i> '.__( 'New linked member', 'doliconnect').'</center></button>';

print "<li class='list-group-item list-group-item-info'><i class='fas fa-info-circle'></i> <b>".__( 'Please contact us to link a pre-existing member', 'doliconnect')."</b></li>"; 

if ( !isset($linkedmember->error) && $linkedmember != null ) { 
foreach ( $linkedmember as $member ) {                                                                                 
print "<li class='list-group-item d-flex justify-content-between lh-condensed list-group-item-action'>";
print doliaddress($member);
if (1 == 1) {
print "<div class='col-4 col-sm-3 col-md-2 btn-group-vertical' role='group'>";
print "<button type='button' class='btn btn-light text-primary' data-toggle='modal' data-target='#member-".$member->id."' title='".__( 'Edit', 'doliconnect')." ".$member->firstname." ".$member->lastname."'><i class='fas fa-edit fa-fw'></i></a>
<button name='unlink_member' value='".$member->id."' class='btn btn-light text-danger' type='submit' title='".__( 'Unlink', 'doliconnect')." ".$member->firstname." ".$member->lastname."'><i class='fas fa-unlink'></i></button>";
print "</div>";
}
print "</li>";
}
} else { 
print "<li class='list-group-item list-group-item-light'><center>".__( 'No linked member', 'doliconnect')."</center></li>";
}
print "</form>";
print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('member'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

if ( !isset($linkedmember->error) && $linkedmember != null ) { 
foreach ( $linkedmember as $member ) {

print '<div class="modal fade" id="member-'.$member->id.'" tabindex="-1" role="dialog" aria-labelledby="member-'.$member->id.'Title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
<div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content border-0"><div class="modal-header border-0">
<h5 class="modal-title" id="member-'.$member->id.'Title">'.__( 'Update member', 'doliconnect').'</h5><button id="Closemember'.$member->id.'-form" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
<div id="member'.$member->id.'-form">';
print "<form class='was-validated' role='form' action='$url' id='member-".$member->id."-form' method='post'>";

print dolimodalloaderscript('member'.$member->id.'-form');

print doliuserform($member, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null), true), 'member'); 

print "</div>".doliloading('member'.$member->id.'-form');
     
print "<div id='Footermember".$member->id."-form' class='modal-footer'><button name='update_member' value='".$member->id."' class='btn btn-warning btn-block' type='submit'>".__( 'Update', 'doliconnect')."</button></form></div>
</div></div></div>";
}}

}

//*****************************************************************************************

if ( !empty(doliconst('MAIN_MODULE_TICKET')) ) {
add_action( 'settings_doliconnect_menu', 'tickets_menu', 1, 1);
add_action( 'settings_doliconnect_tickets', 'tickets_module');
}

function tickets_menu( $arg ) {
print "<a href='".esc_url( add_query_arg( 'module', 'tickets', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ( $arg == 'tickets' ) { print " active"; }
print "'>".__( 'Help', 'doliconnect')."</a>";
}

function tickets_module( $url ) {
global $current_user;

if ( isset($_GET['id']) && $_GET['id'] > 0 ) {  

$request = "/tickets/".esc_attr($_GET['id']);

$ticketfo = callDoliApi("GET", $request, null, dolidelay('ticket', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $ticket;
}

if ( isset($_GET['id']) && isset($_GET['ref']) && ( doliconnector($current_user, 'fk_soc') == $ticketfo->socid ) && ($_GET['ref'] == $ticketfo->ref ) ) {

if ( isset($_POST["case"]) && $_POST["case"] == 'messageticket' ) {
$rdr = [
    'track_id' => $ticketfo->track_id,
    'message' => sanitize_textarea_field($_POST['ticket_newmessage']),
	];                  
$ticketid = callDoliApi("POST", "/tickets/newmessage", $rdr, dolidelay('ticket', true));
//print $ticketid;

if ( $ticketid > 0 ) {
print dolialert ('success', __( 'Your message has been send.', 'doliconnect'));
$ticketfo = callDoliApi("GET", $request, null, dolidelay('ticket', true));
//print $ticket;
} }

print "<div class='card shadow-sm'><div class='card-body'><h5 class='card-title'>".$ticketfo->ref."</h5><div class='row'><div class='col-md-6'>";
$dateticket =  wp_date('d/m/Y', $ticketfo->datec);
print "<b>".__( 'Date of creation', 'doliconnect').": </b> $dateticket<br>";
print "<b>".__( 'Type and category', 'doliconnect').": </b> ".__($ticketfo->type_label, 'doliconnect').", ".__($ticketfo->category_label, 'doliconnect')."<br>";
print "<b>".__( 'Severity', 'doliconnect').": </b> ".__($ticketfo->severity_label, 'doliconnect')."<br>";
print "</div><div class='col-md-6'><h3 class='text-right'>";
if ( $ticketfo->fk_statut == 9 ) { print "<span class='label label-default'>".__( 'Deleted', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 8 ) { print "<span class='label label-success'>".__( 'Closed', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 6 ) { print "<span class='label label-warning'>".__( 'Waiting', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 5 ) { print "<span class='label label-warning'>".__( 'In progress', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 4 ) { print "<span class='label label-warning'>".__( 'Assigned', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 3 ) { print "<span class='label label-warning'>".__( 'Answered', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 1 ) { print "<span class='label label-warning'>".__( 'Read', 'doliconnect')."</span>"; }
elseif ( $ticketfo->fk_statut == 0 ) { print "<span class='label label-danger'>".__( 'Unread', 'doliconnect')."</span>"; }
print "</h3></div></div>";
print '<br><div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: '.$ticketfo->progress.'%" aria-valuenow="'.$ticketfo->progress.'" aria-valuemin="0" aria-valuemax="100"></div></div>';
print "</div><ul class='list-group list-group-flush'>
<li class='list-group-item'><h5 class='mb-1'>".__( 'Subject', 'doliconnect').": ".$ticketfo->subject."</h5>
<p class='mb-1'>".__( 'Initial message', 'doliconnect').": ".$ticketfo->message."</p></li>";

if ( $ticketfo->fk_statut < '8' && $ticketfo->fk_statut > '0' && !empty(get_option('doliconnectbeta')) ) {
print "<li class='list-group-item'>";

print '<form id="doliconnect-msgticketform" action="'.$url.'&id='.$ticketfo->id.'&ref='.$ticketfo->ref.'" method="post" class="was-validated">';

print doliloaderscript('doliconnect-msgticketform'); 

print '<div class="form-group"><label for="ticketnewmessage"><small>'.__( 'Response', 'doliconnect').'</small></label>
<div class="input-group mb-2"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-comment fa-fw"></i></span></div><textarea class="form-control" name="ticket_newmessage" id="ticket_newmessage" rows="5" required></textarea>
</div></div><input type="hidden" name="case" value="messageticket"><button class="btn btn-danger btn-block" type="submit">'.__( 'Answer', 'doliconnect').'</button></form>';
print "</li>";

}

if ( isset($ticketfo->messages) ) {
foreach ( $ticketfo->messages as $msg ) {
$datemsg =  wp_date('d/m/Y - H:i', $msg->datec);  
print  "<li class='list-group-item'><b>$datemsg $msg->fk_user_action_string</b><br>$msg->message</li>";
}} 
print '</ul><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('ticket'), $ticketfo);
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

} elseif ( isset($_GET['create']) ) {

if ( isset($_POST["case"]) && $_POST["case"] == 'createticket' ) {
$rdr = [        
    'fk_soc' => doliconnector($current_user, 'fk_soc'),
    'fk_user_assign' => $_POST['fk_user_assign'],
    'type_code' => $_POST['ticket_type'],
    'category_code' => $_POST['ticket_category'],
    'severity_code' => $_POST['ticket_severity'],
    'subject' => sanitize_text_field($_POST['ticket_subject']),
    'message' => sanitize_textarea_field($_POST['ticket_message']),
	];                  
$ticketid = callDoliApi("POST", "/tickets", $rdr, dolidelay('ticket', true));
//print $ticketid;

if ( $ticketid > 0 ) {
print dolialert ('success', __( 'Your ticket has been submitted.', 'doliconnect'));
} }

print "<form class='was-validated' id='doliconnect-newticketform' action='".$url."&create' method='post'>";

print doliloaderscript('doliconnect-newticketform'); 

print "<div class='card shadow-sm'><ul class='list-group list-group-flush'><li class='list-group-item'><h5 class='card-title'>".__( 'Open a new ticket', 'doliconnect')."</h5>";
print "<div class='form-group'><label for='inputcivility'><small>".__( 'Type and category', 'doliconnect')."</small></label>
<div class='input-group mb-2'><div class='input-group-prepend'><span class='input-group-text' id='identity'><i class='fas fa-info-circle fa-fw'></i></span></div>";

$type = callDoliApi("GET", "/setup/dictionary/ticket_types?sortfield=pos&sortorder=ASC&limit=100", null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $type;

if ( isset($type) ) { 
print "<select class='custom-select' id='ticket_type'  name='ticket_type'>";
if ( count($type) > 1 ) {
print "<option value='' disabled selected >".__( '- Select -', 'doliconnect')."</option>";
}
foreach ($type as $postv) {
print "<option value='".$postv->code."' ";
if ( isset($_GET['type']) && $_GET['type'] == $postv->code ) {
print "selected ";
} elseif ( $postv->use_default == 1 ) {
print "selected ";}
print ">".$postv->label."</option>";
}
print "</select>";
}

$cat = callDoliApi("GET", "/setup/dictionary/ticket_categories?sortfield=pos&sortorder=ASC&limit=100", null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

if ( isset($cat) ) { 
print "<select class='custom-select' id='ticket_cat'  name='ticket_category'>";
if ( count($cat) > 1 ) {
print "<option value='' disabled selected >".__( '- Select -', 'doliconnect')."</option>";
}
foreach ( $cat as $postv ) {
print "<option value='".$postv->code."' ";
if ( $postv->use_default == 1 ) {
print "selected ";}
print ">".$postv->label."</option>";
}
print "</select>";
} 
print "</div></div>";
print "<div class='form-group'><label for='inputcivility'><small>".__( 'Severity', 'doliconnect')."</small></label>
<div class='input-group mb-2'><div class='input-group-prepend'><span class='input-group-text' id='identity'><i class='fas fa-bug fa-fw'></i></span></div>";

$severity = callDoliApi("GET", "/setup/dictionary/ticket_severities?sortfield=pos&sortorder=ASC&limit=100", null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));

if ( isset($severity) ) { 
print "<select class='custom-select' id='ticket_severity'  name='ticket_severity'>";
if ( count($severity) > 1 ) {
print "<option value='' disabled selected >".__( '- Select -', 'doliconnect')."</option>";
}
foreach ( $severity as $postv ) {
print "<option value='".$postv->code."' ";
if ( $postv->use_default == 1 ) {
print "selected ";}
print ">".$postv->label."</option>";
}
print "</select>";
}
print "</div></div>";

if ( doliversion('11.0.0') ) {
print "<div class='form-group'><label for='inputcivility'><small>".__( 'Sales representative', 'doliconnect')."</small></label>
<div class='input-group mb-2'><div class='input-group-prepend'><span class='input-group-text' id='identity'><i class='fas fa-user-tie fa-fw'></i></span></div>";
$representatives = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc')."/representatives?mode=0", null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));  
//print $type;

if ( !isset($representatives->error) && $representatives != null ) {
print "<select class='custom-select' id='fk_user_assign'  name='fk_user_assign' required>";
if ( count($representatives) > 1 ) {
print "<option value='' disabled selected >".__( '- Select -', 'doliconnect')."</option>";
}
foreach ($representatives as $postv) {
print "<option value='".$postv->id."' >".$postv->firstname." ".$postv->lastname;
if (!empty($postv->job)) print ", ".$postv->job;
print "</option>";
}
print "</select>";
} else {
print "<select class='custom-select' id='fk_user_assign' name='fk_user_assign' disabled></select>";
}
print "</div></div>";
}

print "<div class='form-group'><label for='ticket_subject'><small>".__( 'Subject', 'doliconnect')."</small></label><div class='input-group mb-2'><div class='input-group-prepend'><div class='input-group-text'><i class='fas fa-bullhorn fa-fw'></i></div></div><input type='text' class='form-control' id='ticket_subject' name='ticket_subject' value='' autocomplete='off' required></div></div>";

print "<div class='form-group'>
<label for='description'><small>".__( 'Message', 'doliconnect')."</small></label><div class='input-group mb-2'><div class='input-group-prepend'><span class='input-group-text'><i class='fas fa-file-alt fa-fw'></i></span></div>
<textarea type='text' class='form-control' name='ticket_message' id='ticket_message' rows='8' required></textarea></div></div></li></ul>";

print "<div class='card-body'><input type='hidden' name='case' value='createticket'><button type='submit' class='btn btn-block btn-warning'>".__( 'Send', 'doliconnect')."</button></div>";

print "</div></form>";

} else {

$request = "/tickets?socid=".doliconnector($current_user, 'fk_soc')."&sortfield=t.rowid&sortorder=DESC&limit=10";

$listticket = callDoliApi("GET", $request, null, dolidelay('ticket', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
//print $listticket;

print '<div class="card shadow-sm"><ul class="list-group list-group-flush">';
//if ($help>0) {
print '<a href="'.$url.'&create" class="list-group-item lh-condensed list-group-item-action list-group-item-primary"><center><i class="fas fa-plus-circle"></i> '.__( 'New ticket', 'doliconnect').'</center></a>';  
//}
if ( !isset($listticket->error) && $listticket != null ) {
foreach ($listticket as $postticket) {                                                                                 

$arr_params = array( 'id' => $postticket->id, 'ref' => $postticket->ref);  
$return = esc_url( add_query_arg( $arr_params, $url) );

if ( $postticket->severity_code == 'BLOCKING' ) { $color="text-danger"; } 
elseif ( $postticket->severity_code == 'HIGH' ) { $color="text-warning"; }
elseif ( $postticket->severity_code == 'NORMAL' ) { $color="text-success"; }
elseif ( $postticket->severity_code == 'LOW' ) { $color="text-info"; } else { $color="text-dark"; }
print "<a href='$return' class='list-group-item d-flex justify-content-between lh-condensed list-group-item-light list-group-item-action'><div><i class='fas fa-question-circle $color fa-3x fa-fw'></i></div><div><h6 class='my-0'>$postticket->subject</h6><small class='text-muted'>du ".wp_date('d/m/Y', $postticket->datec)."</small></div><span class='text-center'>".__($postticket->type_label, 'doliconnect')."<br/>".__($postticket->category_label, 'doliconnect')."</span><span>";
if ( $postticket->fk_statut == 9 ) { print "<span class='label label-default'>".__( 'Deleted', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 8 ) { print "<span class='label label-success'>".__( 'Closed', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 6 ) { print "<span class='label label-warning'>".__( 'Waiting', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 5 ) { print "<span class='label label-warning'>".__( 'Progress', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 4 ) { print "<span class='label label-warning'>".__( 'Assigned', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 3 ) { print "<span class='label label-warning'>".__( 'Answered', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 1 ) { print "<span class='label label-warning'>".__( 'Read', 'doliconnect')."</span>"; }
elseif ( $postticket->fk_statut == 0 ) { print "<span class='label label-danger'>".__( 'Unread', 'doliconnect')."</span>"; }
print "</span></a>";
}}
else{
print "<li class='list-group-item list-group-item-light'><center>".__( 'No ticket', 'doliconnect')."</center></li>";
}

print '</ul><div class="card-body"></div><div class="card-footer text-muted">';
print "<small><div class='float-left'>";
if ( isset($request) ) print dolirefresh($request, $url, dolidelay('ticket'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

}
}

function settings_menu($arg) {
print "<a href='".esc_url( add_query_arg( 'module', 'settings', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='settings') { print " active"; }
print "'>".__( 'Settings & security', 'doliconnect')."</a>";
}
add_action( 'settings_doliconnect_menu', 'settings_menu', 2, 1);

function settings_module($url) {
global $wpdb, $current_user;

$ID = $current_user->ID;

print "<form id='settings-form' method='post' action='".admin_url('admin-ajax.php')."'>";
print "<input type='hidden' name='action' value='dolisettings_request'>";
print "<input type='hidden' name='dolisettings-nonce' value='".wp_create_nonce( 'dolisettings-nonce')."'>";

print "<script>";
print 'function DoliSettings(theForm){
jQuery("#DoliconnectLoadingModal").modal("show");
jQuery("#DoliconnectLoadingModal").on("shown.bs.modal", function (e) { 
    $.ajax({ // create an AJAX call...
        data: $(theForm).serialize(), // get the form data
        type: $(theForm).attr("method"), // GET or POST
        url: $(theForm).attr("action"), // the file to call
        success: function (response) { // on success..
        jQuery("#DoliconnectLoadingModal").modal("hide");
        //alert(response.data);
        }
    });
});
}';
print "</script>";

print "<div class='card shadow-sm'><ul class='list-group list-group-flush'>";
print "<li class='list-group-item list-group-item-light list-group-item-action'><div class='custom-control custom-switch'><input type='checkbox' class='custom-control-input' name='loginmailalert' id='loginmailalert' ";
if ( defined("DOLICONNECT_DEMO") && ''.constant("DOLICONNECT_DEMO").'' == $ID ) {
print " disabled";
} elseif ( $current_user->loginmailalert == 'on' ) { print " checked"; }        
print " onchange='DoliSettings(this.form)'><label class='custom-control-label w-100' for='loginmailalert'> ".__( 'Receive a email notification at each connection', 'doliconnect')."</label>
</div></li>";
if ( !empty(get_option('doliconnectbeta')) ) {
print "<li class='list-group-item list-group-item-light list-group-item-action'><div class='custom-control custom-switch'><input type='checkbox' class='custom-control-input' name='optin1' id='optin1' ";
if ( $current_user->optin1 == 'on' ) { print " checked"; }        
print " onchange='DoliSettings(this.form)'><label class='custom-control-label w-100' for='optin1'> ".__( 'I would like to receive the newsletter', 'doliconnect')."</label>
</div></li>";
print "<li class='list-group-item list-group-item-light list-group-item-action'><div class='custom-control custom-switch'><input type='checkbox' class='custom-control-input' name='optin2' id='optin2' ";
if ( $current_user->optin2 == 'on' ) { print " checked"; }        
print " onchange='DoliSettings(this.form)'><label class='custom-control-label w-100' for='optin2'> ".__( 'I would like to receive the offers of our partners', 'doliconnect')."</label>
</div></li>";
}
$privacy=$wpdb->prefix."doliprivacy";
if ( $current_user->$privacy ) {
print "<li class='list-group-item list-group-item-light list-group-item-action'>";
print "".__( 'Approval of the Privacy Policy the', 'doliconnect')." ".wp_date( get_option( 'date_format' ).' - '.get_option('time_format'), $current_user->$privacy, false)."";
print "</li>";
}
print "<li class='list-group-item list-group-item-light list-group-item-action'>";
//print $current_user->locale;
print "<div class='form-group'><label for='inputaddress'><small>".__( 'Default language', 'doliconnect')."</small></label>
<div class='input-group'><div class='input-group-prepend'><span class='input-group-text'><i class='fas fa-language fa-fw'></i></span></div>";
if ( function_exists('pll_the_languages') ) { 
print "<select class='form-control' id='locale' name='locale' onchange='DoliSettings(this.form)' >";
print "<option value=''>".__( 'Default / Browser language', 'doliconnect')."</option>";
$translations = pll_the_languages( array( 'raw' => 1 ) );
foreach ($translations as $key => $value) {
print "<option value='".str_replace("-","_",$value[locale])."' ";
if  ( $current_user->locale == str_replace("-","_",$value['locale']) ) {print " selected";}
print ">".$value['name']."</option>";
}
print "</select>";
} else {
print "<input class='form-control' type='text' value='".__( 'Default / Browser language', 'doliconnect')."' readonly>";
}
print "</div></div>";
//print pll_default_language('locale');
print "</li>";

if ( doliconnector($current_user, 'fk_soc') > 0 ) {
$thirdparty = callDoliApi("GET", "/thirdparties/".doliconnector($current_user, 'fk_soc'), null, dolidelay('thirdparty', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
}

$currencies = callDoliApi("GET", "/setup/dictionary/currencies?multicurrency=1&sortfield=code_iso&sortorder=ASC&limit=100&active=1", null, dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
 
print "<li class='list-group-item list-group-item-light list-group-item-action'>";
//print $current_user->locale;
print "<div class='form-group'><label for='inputaddress'><small>".__( 'Default currency', 'doliconnect')."</small></label>
<div class='input-group'><div class='input-group-prepend'><span class='input-group-text'><i class='fas fa-money-bill-alt fa-fw'></i></span></div>";
print "<select class='form-control' id='multicurrency_code' name='multicurrency_code' onchange='DoliSettings(this.form)' ";
$monnaie = doliconst("MAIN_MONNAIE", dolidelay('constante', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null)));
if ( empty(doliconst('MAIN_MODULE_MULTICURRENCY', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null))) || !doliversion('11.0.0') ) { print " disabled"; }
print ">";
if ( !isset( $currencies->error ) && $currencies != null && !empty(doliconst('MAIN_MODULE_MULTICURRENCY', esc_attr(isset($_GET["refresh"]) ? $_GET["refresh"] : null))) && doliversion('11.0.0')) {
foreach ( $currencies as $currency ) { 
print "<option value='".$currency->code_iso."' ";
if ( $currency->code_iso == $thirdparty->multicurrency_code ) { print " selected"; }
print ">".$currency->code_iso." / ".doliprice(1.99*$currency->rate, null, $currency->code_iso)."</option>";
}} else {
$cur = (!empty($thirdparty->multicurrency_code) ? $thirdparty->multicurrency_code : $monnaie );
print "<option value='".$cur."' selected>".$cur." / ".doliprice('1.99', null, $cur)."</option>";
}
print "</select>";
print "</div></div>";
print "</li>";
print "<div class='card-body'>";
if ( is_plugin_active( 'two-factor/two-factor.php' ) && current_user_can('administrator') && !empty(get_option('doliconnectbeta')) ) {
require_once( ABSPATH . 'wp-content/plugins/two-factor/class-two-factor-core.php')

		?>
					<table class="table">
						<thead>
							<tr>
								<th ><?php esc_html_e( 'Enabled',  'doliconnect'); ?></th>
								<th ><?php esc_html_e( 'Primary',  'doliconnect'); ?></th>
								<th ><?php esc_html_e( 'Description',  'doliconnect'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( Two_Factor_Core::get_providers() as $class => $object ) : ?>
							<tr>
								<td><input type="checkbox" class="" name="<?php echo esc_attr( Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY ); ?>[]" value="<?php echo esc_attr( $class ); ?>" <?php checked( in_array( $class, $providers ) ); ?> /></td>
								<td><input type="radio" class="" name="<?php echo esc_attr( Two_Factor_Core::PROVIDER_USER_META_KEY ); ?>" value="<?php echo esc_attr( $class ); ?>" <?php checked( $class, $primary_provider_key ); ?> /></td>
								<td>
									<?php $object->print_label(); ?>
									<?php do_action( 'two-factor-user-options-' . $class, $current_user ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
		<?php
		//do_action( 'show_user_security_settings', $current_user );
} else {
print __( 'Two factor authentication is disabled', 'doliconnect');
}
print "</div>";
print '</ul><div class="card-footer text-muted"></form>';
print "<small><div class='float-left'>";
print dolirefresh( "/thirdparties/".doliconnector($current_user, 'fk_soc'), $url, dolidelay('member'));
print "</div><div class='float-right'>";
print dolihelp('ISSUE');
print "</div></small>";
print '</div></div>';

if ( !empty(get_option('doliconnectbeta')) ) { 

print '<style>';
?>
.blur{
  -webkit-filter: blur(5px);
  -moz-filter: blur(5px);
  -o-filter: blur(5px);
  -ms-filter: blur(5px);
  filter: blur(5px);
}
<?php
print '</style>';

function generate_license($suffix = null) {
    // Default tokens contain no "ambiguous" characters: 1,i,0,o
    if(isset($suffix)){
        // Fewer segments if appending suffix
        $num_segments = 3;
        $segment_chars = 6;
    }else{
        $num_segments = 5;
        $segment_chars = 5;
    }
    $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $license_string = '';
    // Build Default License String
    for ($i = 0; $i < $num_segments; $i++) {
        $segment = '';
        for ($j = 0; $j < $segment_chars; $j++) {
            $segment .= $tokens[rand(0, strlen($tokens)-1)];
        }
        $license_string .= $segment;
        if ($i < ($num_segments - 1)) {
            $license_string .= '-';
        }
    }
    // If provided, convert Suffix
    if(isset($suffix)){
        if(is_numeric($suffix)) {   // Userid provided
            $license_string .= '-'.strtoupper(base_convert($suffix,10,36));
        }else{
            $long = sprintf("%u\n", ip2long($suffix),true);
            if($suffix === long2ip($long) ) {
                $license_string .= '-'.strtoupper(base_convert($long,10,36));
            }else{
                $license_string .= '-'.strtoupper(str_ireplace(' ','-',$suffix));
            }
        }
    }
    return $license_string;
}

//print generate_license();

}

}
add_action( 'settings_doliconnect_settings', 'settings_module');

function gdpr_menu($arg) {
print "<a href='".esc_url( add_query_arg( 'module', 'gdpr', doliconnecturl('doliaccount')) )."' class='list-group-item list-group-item-light list-group-item-action";
if ($arg=='gdpr') { print " active";}
print "'>".__( 'Privacy', 'doliconnect')."</a>";
}
add_action( 'settings_doliconnect_menu', 'gdpr_menu', 3, 1);
add_action( 'settings_doliconnect_gdpr', 'gdpr_module');
 
function gdpr_module($url) {
global $current_user;

		$params = array();
		if ( isset( $instance['request_type'] ) ) {
			if ( 'export' === $instance['request_type'] ) {
				$params['request_type'] = 'export';
			} elseif ( 'remove' === $instance['request_type'] ) {
				$params['request_type'] = 'remove';
			}
		}
		print doli_gdrf_data_request_form( $params ); 

}

?>
