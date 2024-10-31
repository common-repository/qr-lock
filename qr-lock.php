<?php
/*
Plugin Name: QR Lock
Plugin URI:  http://www.miguelpiedrahita.com/
Description: Administrar los contenidos web mediante un unico codigo QR
Version:     1.0
Author:      Miguel Piedrahita
Author URI:  http://www.miguelpiedrahita.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
//incluir libreria qr
include('phpqrcode/qrlib.php');
//registrar funcion de menu
add_action( 'init', 'qr_lock_mi_menu' );
//imagen y tipo del menu administracion
function qr_lock_mi_menu() {
	register_post_type( 'qrlock',
		array(
			'labels' => array(
				'name' => __( 'QR Lock' ),
				'singular_name' => __( 'QR Lock' ),
				'add_new' => __( 'Añadir QR Lock'),
				'add_new_item' => __( 'Añadir QR Lock')
			),
			'show_ui' => true,
			'description' => 'Post type for QR Lock',
			//'menu_position' => 5,
			'menu_icon' => WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . '/imagenes/my_icon.png',
			'public' => true,
			'exclude_from_search' => true,
			'supports' => array('title'),
			'rewrite' => array('slug' => 'qr'),
			'can_export' => true
		)
	);
}
//contador de visitas
function contador_visitas($post_id) {
	$cont = get_post_meta($post_id,'conta_qrlock_cont',true);
	if(!$cont) {
		$cont = 0;
	}
	$cont = $cont + 1;
	update_post_meta($post_id,'conta_qrlock_cont',$cont);
}
// activar redireccion
add_action( 'wp', 'qr_lock_redirigir' );
//redirigir url
function qr_lock_redirigir() {
	global $post;
	//para la compatibilidad con versiones anteriores
	if(!isset($post->ID)) {
		//obtener la post_name para que podamos mirar hacia arriba del poste
		if(stristr($_SERVER['REQUEST_URI'], "/") && stristr($_SERVER['REQUEST_URI'], "/qr/")) {
			$uri = explode("/", $_SERVER['REQUEST_URI']);
			foreach($uri as $i => $u) {
				if($u == '') {
					unset($uri[$i]);
				}
			}
			$uri = array_pop($uri);
		}
		else {
			$uri = $_SERVER['REQUEST_URI'];
		}
	
		$post = get_page_by_path($uri,'OBJECT','qrlock');
	}
	
	if(!is_admin()) {
		if(isset($post->post_type) && $post->post_type == 'qrlock') {
			$url = get_post_meta($post->ID, 'url_qrlock_url', true);

			if($url != '') {
				contador_visitas($post->ID);
				header( 'Location: '.$url );
				exit();
			}
			else {
				//si por alguna razón no hay url, redirigir a la página de inicio
				header( 'Location: '.get_bloginfo('url') );
				exit();
			}
		}
	}
}
//cuadros flontes
add_action( 'add_meta_boxes', 'cuadros_informativos' );
//funcion de cuadros QR Lock con informacion
function cuadros_informativos() {
    //la url de redireccionamiento
	add_meta_box(
		'dynamic_url',
		__( '<div id="tit" style="color:#58ACFA; text-align: center;">Creador de QR Lock</div>', 'myplugin_textdomain' ),
		'creador_qr_lock',
		'qrlock');
        
	//generando el código QR
	add_meta_box(
		'dynamic_qr',
		__( '<div id="tit" style="color:#58ACFA; text-align: center;">QR lock Informativo</div>', 'myplugin_textdomain' ),
		'informacion_qr_lock',
		'qrlock',
		'side');
}
// cuadro de creacion del QR Lock
function creador_qr_lock() {
    global $post;
    // Utilice nonce para la verificación
    wp_nonce_field( plugin_basename( __FILE__ ), 'dynamicMeta_noncename' );
    
    echo '<div id="info">';
    //obtener los metadatos guardado
    $url = get_post_meta($post->ID,'url_qrlock_url',true);
    $ecl = get_post_meta($post->ID,'nivelc_qrlock_ecl',true);
    $size = get_post_meta($post->ID,'tama_qrlock_size',true);
    $notes = get_post_meta($post->ID,'notas_qrlock_notes',true);
    
    //salida de la forma
	echo '<p> <strong><abbr title="Sitio Web acompartir.">URL:</abbr></strong> <input type="text" name="url_qrlock[url]" size="50" value="'.$url.'" /> </p><hr>';
	echo '<p><strong><abbr title="Solo visible en panel de administacion">Notas Administrador:</abbr></strong><br /> <textarea style="width: 75%; height: 150px; color:#58ACFA;" name="notas_qrlock[notes]">'.$notes.'</textarea></p>';
	echo '<hr>';
	echo '<p><strong><abbr title="Nivel de daño.">Nivel en Error Corrección:</abbr></strong> <select name="nivelc_qrlock[ecl]">';
	echo '<option value="L"';
	if($ecl == "L") { echo ' selected="selected"'; }
	echo '>L - Recuperación de la pérdida de datos hasta un 7%</option>';
	echo '<option value="M"';
	if($ecl == "M") {
		echo ' selected="selected"';
	}
	echo '>M - Recuperación de la pérdida de datos hasta un 15%</option>';
	echo '<option value="Q"';
	if($ecl == "Q") {
		echo ' selected="selected"';
	}
	echo'>Q - Recuperación de la pérdida de datos hasta un 25%</option>';
	echo '<option value="H"';
	if($ecl == "H") {
		echo ' selected="selected"';
	}
	echo '>H - Recuperación de la pérdida de datos hasta un 30%</option>';
	echo '</select></p>';
	
	echo '<p><strong><abbr title="Tamaño de Imagen QR Lock.">Tamaño:</abbr></strong> <select name="tama_qrlock[size]">';
	for($i=1; $i<=30; $i++) {
		echo '<option value="'.$i.'"';
		if($size == $i) {
			echo ' selected="selected"';
		}
		echo '>'.$i.'</option>';
	}
	echo '</select></p>';
	echo '<hr/>';
	
	if($post->post_status !='auto-draft') {
		//después aún no se ha guardado si el estado es auto-proyecto
		echo '<p><strong><abbr title="Código corto que permite añadir el QR Lock en tus páginas, comentarios y entradas.">Shortcode:</abbr></strong><br />';
		echo 'Copia y pega este código en las páginas, comentarios y entradas donde desees visualizar el Código QR de QR Lock:';
		
		echo '<br /><br /><code>[qr-lock id="'.$post->ID.'"]</code></p>';
	}
	
	if($post->post_status !='auto-draft') {
		echo '<p>';
		echo '<strong><abbr title="Previsualizacion del Shortcode.">Tamaño Actual y Previsualizacion:</abbr></strong></br >';
		echo do_shortcode('[qr-lock id="'.$post->ID.'"]');
		echo '</p>';
	}
	echo '</div>';
	
}
// cuadro solo infrmativo
function informacion_qr_lock() {
    global $post;
    $img = get_post_meta($post->ID, 'qr_image_url', true);
    
    echo '<div id="info" style="text-align: center;">';
	if($post->post_status == "publish") {
		echo get_bloginfo('url');
		echo '<br /><br />';
		echo '<img src="'.$img.'" style="max-width: 250px; max-height: 250px;" />';
		echo '<br /><br />QR Lock<br /><br />';
		echo get_post_meta($post->ID,'url_qrlock_url',true);
		
		$cont = get_post_meta($post->ID,'conta_qrlock_cont',true);
		if(!$cont) {
			$cont = 0;
		}
		echo '<br><meter value="'.$cont.'" min="0" max="10"> </meter>';
		echo '<br /><br />Contenido Visitado <strong>'.$cont.'</strong> Veces';
		echo '<br /><br />';
		echo '<a href="http://qrlock.html-5.me" target="_blank"><button type="button">Mas Información!</button></a>';
	}
	else {
		echo 'visualizador de tamaño e información relevante';
	}
	echo '</div>';
	
}
//guardar post y datos 
add_action( 'save_post', 'guardar_editar' );
// guardar
function guardar_editar( $post_id ) {
	//si el formulario no se ha presentado, no queremos hacer nada
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { 
		return;
	}
	// verifique este vino de la nuestra pantalla y con la debida autorización
	if (isset($_POST['dynamicMeta_noncename'])){
		if ( !wp_verify_nonce( $_POST['dynamicMeta_noncename'], plugin_basename( __FILE__ ) ) )
			return;
	}
	else {
		return;
	}
	//guardar los datos
	$url = $_POST['url_qrlock']['url'];
	if(!stristr($url, "://")) {
		$url = "http://".$url;
	}
	$permalink = get_permalink($post_id);
	$errorCorrectionLevel = $_POST['nivelc_qrlock']['ecl'];
	$matrixPointSize = $_POST['tama_qrlock']['size'];
	//generar el archivo de imagen
	$upload_dir = wp_upload_dir();
	$PNG_TEMP_DIR = $upload_dir['basedir'].'/qrlocks/';
	if (!file_exists($PNG_TEMP_DIR)) {
		mkdir($PNG_TEMP_DIR);
	}
	//formulario de entrada de procesamiento
	$filename = $PNG_TEMP_DIR.'qr'.md5($permalink.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
	//si estamos actualizando una imagen, no queremos mantener la versión antigua
	$oldfile = str_replace($upload_dir['baseurl'].'/qrlocks/', $PNG_TEMP_DIR, get_post_meta($post_id,'qr_image_url',true));
	if ($oldfile != '' && file_exists($oldfile)) {
		unlink($oldfile);
	}
	QRcode::png($permalink, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
	$img = content_url().'/uploads/qrlocks/'.basename($filename);
	update_post_meta($post_id,'qr_image_url',$img);
	update_post_meta($post_id,'url_qrlock_url',$url);
	update_post_meta($post_id,'nivelc_qrlock_ecl',$errorCorrectionLevel);
	update_post_meta($post_id,'tama_qrlock_size',$matrixPointSize);
	update_post_meta($post_id,'notas_qrlock_notes',$_POST['notas_qrlock']['notes']);
}
//activar shortcode
add_shortcode( 'qr-lock', 'codigo_short');
//crear shortcode para poder incluir informacion en llas publicaciones
function codigo_short($atts) {
	extract( shortcode_atts( array(
		'id' => ''
	), $atts ) );
	//si no se especifica la identificación, no tenemos nada que mostrar
	if(!$id) {
		return false;
	}
	$output = '';
	$cont = get_post_meta($id,'conta_qrlock_cont',true);
	$img = get_post_meta($id, 'qr_image_url', true);
	$output .= '<img src="'.$img.'" class="qr-lock" /><br/><strong>Visitado: </strong>'.$cont.' <meter value="'.$cont.'" min="0" max="10"> </meter>';	
	return $output;
}
?>