<?php
if(!defined('ABSPATH')) exit; // Exclu en cas d'accès direct par l'URL du fichier

/*--------------------------------------------*/
/*--------- Fonction d'autocomplétion --------*/
/*--------------------------------------------*/
// Ajout conditionné du fichier d'autocomplétion
function WP_Advanced_Search_AutoCompletion() {
	global $wpdb, $tableName, $link;

	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");

	// Lancement de la fonction d'autocomplétion si activé...
	if($select->autoCompleteActive == 1) {
		// Autocomplete style
		$urlstyle = plugins_url('css/jquery.autocomplete.min.css',__FILE__);
		wp_enqueue_style('js-autocomplete-style', $urlstyle, false, '1.0');
	}
}
add_action('wp_enqueue_scripts', 'WP_Advanced_Search_AutoCompletion');

// Ajout conditionné du système d'autocomplétion
function addAutoCompletion() {
	global $wpdb, $tableName;
	
	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");

	// Lancement de la fonction d'autocomplétion si activé...
	if($select->autoCompleteActive == 1) {
		// Instanciation des variables utiles
		$selector		= $select->autoCompleteSelector;
		$limitDisplay	= $select->autoCompleteNumber;
		$multiple		= $select->autoCompleteTypeSuggest ? true : false;
		$autoFocus		= $select->autoCompleteAutofocus ? true : false;

		// Paramètres Ajax
		wp_enqueue_script('params-autocomplete', plugins_url("js/autocompleteSearch-min.js", __FILE__ ), array('jquery-ui-core', 'jquery-ui-autocomplete'), false, false);
		$scriptData = array(
			'ajaxurl' => admin_url('/admin-ajax.php'),
			'selector' => $selector,
			'autoFocus' => $autoFocus,
			'limitDisplay' => $limitDisplay,
			'multiple' => $multiple,
		);
		wp_localize_script('params-autocomplete', 'ac_param', $scriptData);
	}
}
add_action('wp_enqueue_scripts', 'addAutoCompletion');

/*-----------------------------------------*/
/*-------- Fonction autocomplétion --------*/
/*-----------------------------------------*/
// J'utilise les hooks
add_action('wp_ajax_wpas_autocomplete', 'WP_Advanced_Search_Autocomplete_Action');
add_action('wp_ajax_nopriv_wpas_autocomplete', 'WP_Advanced_Search_Autocomplete_Action');
function WP_Advanced_Search_Autocomplete_Action() {
	global $wpdb, $tableName;
	
	// Sélection des données dans la base de données		
	$select = $wpdb->get_row("SELECT * FROM ".filter_var($wpdb->prefix.$tableName, FILTER_SANITIZE_STRING)." WHERE id=1");

	// Instanciation des variables utiles
	$tableNameAC	= htmlspecialchars(filter_var($select->autoCompleteTable, FILTER_SANITIZE_STRING));
	$tableColumn	= htmlspecialchars(filter_var($select->autoCompleteColumn, FILTER_SANITIZE_STRING));
	$acQuery 		= sanitize_text_field($_POST['ac_query']);
	$acQuery		= str_ireplace('"', '&quot;', $acQuery);
	$type			= htmlspecialchars(filter_var($select->autoCompleteType, FILTER_SANITIZE_STRING));
	// $encode 		= htmlspecialchars(filter_var($select->encoding, FILTER_SANITIZE_STRING));

	// Détermine le type de requête
	if($type == 0 || $type > 1) {
		$arg = "";
	} else {
		$arg = "%";	
	}

	// Requête
	global $wpdb;
	$results = $wpdb->get_results("SELECT ".$tableColumn." FROM ".$tableNameAC." WHERE ".$tableColumn." LIKE '".$arg.$acQuery."%'");
	$items = array();
	if(!empty($results)) {
		foreach($results as $result) {
			$result->words = str_ireplace("&quot;", '"', $result->words);
			$items[] = $result->words;
		}
		sort($items);
	}
	echo json_encode($items);
	die();
}

/*--------------------------------------------*/
/*-------- Fonction trigger et scroll --------*/
/*--------------------------------------------*/
include_once('class.inc/ajaxResults.php'); // Fichier d'affichage Ajax des résultats

// Fonction du trigger
function WP_Advanced_Search_Trigger() {
	global $wpdb, $tableName, $moteur;

	//Récupération des variables utiles dynamiquement
	$select	= $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");

	if($select->paginationType == "trigger") {
		$nameSearch = $select->nameField;							// Nom du champ
		$imgUrl		= plugins_url('img/loadingGrey.gif',__FILE__);	// URL des images choisies
		$duration	= $select->paginationDuration;					// temps d'attente avant la réponse
		$limitR		= $select->paginationNbLimit;					// Pallier d'affichage des résultats

		if(($select->autoCorrectType == 1 || $select->autoCorrectType == 2) && isset($moteur->requeteCorrigee)) {
			$queryAS = $moteur->requeteCorrigee;
		} elseif(isset($_GET[$nameSearch])) {
			$queryAS = stripslashes($_GET[$nameSearch]);
		}

		// Tableau des données envoyées au script
		$scriptData = array(
			'ajaxurl' => admin_url('/admin-ajax.php'),
			'nameSearch' => $nameSearch,
			'query' => trim($queryAS),
			'limitR' => $limitR,
			'duration' => $duration,
			'loadImg' => $imgUrl
		);
		
		// Chargement des variables et des scripts
		wp_enqueue_script('ajaxTrigger', plugins_url('js/ajaxTrigger-min.js',__FILE__), array('jquery'), '1.0');
		wp_enqueue_script('ajaxTriggerStart', plugins_url('js/ajaxTriggerStart-min.js',__FILE__), array('jquery'), '1.0');
		wp_localize_script('ajaxTriggerStart', 'ASTrigger', $scriptData);
	}
}
// Fonction de l'infinite scroll
function WP_Advanced_Search_InfiniteScroll() {
	global $wpdb, $tableName, $moteur;

	//Récupération des variables utiles dynamiquement
	$select	= $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.$tableName." WHERE id=1");
	
	if($select->paginationType == "infinite") {
		$nameSearch = $select->nameField;			// Nom du champ
		$duration	= $select->paginationDuration;	// temps d'attente avant la réponse
		$limitR		= $select->paginationNbLimit;	// Pallier d'affichage des résultats

		if(($select->autoCorrectType == 1 || $select->autoCorrectType == 2) && isset($moteur->requeteCorrigee)) {
			$queryAS = $moteur->requeteCorrigee;
		} elseif(isset($_GET[$nameSearch])) {
			$queryAS = stripslashes($_GET[$nameSearch]);
		}

		// Tableau des données envoyées au script
		$scriptDataIS = array(
			'ajaxurl' => admin_url('/admin-ajax.php'),
			'nameSearch' => $nameSearch,
			'query' => trim($queryAS),
			'limitR' => $limitR,
			'duration' => $duration,
		);
		
		// Chargement des variables et des scripts
		wp_enqueue_script('ajaxInfiniteScroll', plugins_url('js/ajaxInfiniteScroll-min.js',__FILE__), array('jquery'), '1.0');
		wp_enqueue_script('ajaxInfiniteScrollStart', plugins_url('js/ajaxInfiniteScrollStart-min.js',__FILE__), array('jquery'), '1.0');
		wp_localize_script('ajaxInfiniteScrollStart', 'ASInfiniteScroll', $scriptDataIS);
	}
}
?>