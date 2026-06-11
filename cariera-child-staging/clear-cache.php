<?php
/**
 * Script pour vider le cache PHP OPcache
 * Uploadez dans /wp-content/themes/cariera-child/ et accédez une seule fois
 */

echo '<h1>Vidage du Cache PHP</h1><hr>';

if ( function_exists( 'opcache_reset' ) ) {
	if ( opcache_reset() ) {
		echo '<p style="color:green; font-size:20px;">✅ OPcache vidé avec succès !</p>';
	} else {
		echo '<p style="color:red;">❌ Impossible de vider OPcache</p>';
	}
	
	$status = opcache_get_status();
	echo '<h2>Status OPcache :</h2>';
	echo '<pre>';
	print_r( $status );
	echo '</pre>';
} else {
	echo '<p style="color:orange;">⚠️ OPcache n\'est pas activé sur ce serveur</p>';
}

echo '<hr>';
echo '<h2>Autres caches PHP :</h2>';

// APC
if ( function_exists( 'apc_clear_cache' ) ) {
	apc_clear_cache();
	echo '<p style="color:green;">✅ APC Cache vidé</p>';
}

// Realpath cache
if ( function_exists( 'clearstatcache' ) ) {
	clearstatcache( true );
	echo '<p style="color:green;">✅ Realpath cache vidé</p>';
}

echo '<hr>';
echo '<p><strong>Maintenant, rechargez votre page WordPress pour voir si le template se charge !</strong></p>';