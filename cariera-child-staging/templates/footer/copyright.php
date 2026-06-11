<?php
/**
 * Footer: copyright template
 *
 * This template can be overridden by copying it to cariera-child/templates/footer/copyright.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.7
 * @version     1.7.9
 */
?>
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$year = date('Y');
?>

<div class="copyright">
	<div class="container">
		<div class="row">

			<!-- ⬅️ Colonne GAUCHE : Liens légaux (75% de largeur) -->
			<div class="col-md-8 col-sm-8 col-xs-12">
				
				<div class="jobiizy-footer-legal">
					<span class="label">Informations légales :</span>
					
					<a href="/pages/jobiizy-cgu/" target="_blank">CGU</a>
					<span class="sep">•</span>

					<a href="/pages/jobiizy-politiqueconfidentialite/" target="_blank">Politique de confidentialité</a>
<!-- 
					<span class="sep">•</span>
 -->

<!-- 
					<a href="/pages/jobiizy-mentions-legales/" target="_blank">Mentions légales</a>
 -->
				</div>

			</div>

			<!-- ➡️ Colonne DROITE : Copyright + Design (25% de largeur) -->
			<div class="col-md-4 col-sm-4 col-xs-12">
				<div class="jobiizy-footer-copyright">
					<span>Copyright ©<?php echo $year; ?> <a href="/" target="_blank">JobiiZy</a></span>
					<span class="separator">•</span>
					<span>Design : <a href="https://studiologeek.com" target="_blank" rel="noopener">Studiologeek</a></span>
				</div>
			</div>

		</div>
	</div>
</div>