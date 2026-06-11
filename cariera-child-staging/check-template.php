<?php
/**
 * Script de diagnostic pour vérifier le template JobiiZy
 * Placez ce fichier dans /wp-content/themes/cariera-child/ et accédez-y via le navigateur
 * URL: https://staging.jobiizy.com/wp-content/themes/cariera-child/check-template.php
 */

$template_path = __DIR__ . '/cariera-core/elements/listing-half/jobiizy-main-details.php';

echo '<h1>Diagnostic Template JobiiZy</h1>';
echo '<hr>';

echo '<h2>Chemin testé:</h2>';
echo '<pre>' . $template_path . '</pre>';
echo '<hr>';

echo '<h2>Le fichier existe ?</h2>';
if ( file_exists( $template_path ) ) {
	echo '<p style="color: green; font-weight: bold;">✅ OUI - Le fichier existe</p>';
} else {
	echo '<p style="color: red; font-weight: bold;">❌ NON - Le fichier n\'existe pas</p>';
}
echo '<hr>';

echo '<h2>Permissions du fichier:</h2>';
if ( file_exists( $template_path ) ) {
	$perms = fileperms( $template_path );
	$info = sprintf( '%o', $perms );
	echo '<p>Permissions: ' . substr( $info, -4 ) . '</p>';
	
	if ( is_readable( $template_path ) ) {
		echo '<p style="color: green;">✅ Fichier LISIBLE</p>';
	} else {
		echo '<p style="color: red;">❌ Fichier NON LISIBLE</p>';
		echo '<p><strong>Solution:</strong> Changez les permissions à 644</p>';
	}
} else {
	echo '<p style="color: orange;">⚠️ Impossible de vérifier les permissions (fichier introuvable)</p>';
}
echo '<hr>';

echo '<h2>Contenu du répertoire:</h2>';
$dir = __DIR__ . '/cariera-core/elements/listing-half/';
if ( is_dir( $dir ) ) {
	$files = scandir( $dir );
	echo '<ul>';
	foreach ( $files as $file ) {
		if ( $file !== '.' && $file !== '..' ) {
			$full_path = $dir . $file;
			$readable = is_readable( $full_path ) ? '✅' : '❌';
			echo '<li>' . $readable . ' ' . $file . '</li>';
		}
	}
	echo '</ul>';
} else {
	echo '<p style="color: red;">❌ Le répertoire n\'existe pas</p>';
}
echo '<hr>';

echo '<h2>Test d\'inclusion:</h2>';
if ( file_exists( $template_path ) && is_readable( $template_path ) ) {
	echo '<p style="color: green;">✅ Le fichier DEVRAIT fonctionner</p>';
	echo '<p>Si WordPress ne le trouve toujours pas, c\'est probablement un problème de cache.</p>';
} else {
	echo '<p style="color: red;">❌ Le fichier a un problème de permissions</p>';
}