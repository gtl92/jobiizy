<?php
/**
 * AJAX Handler pour charger les détails des offres
 * Version ultra-propre sans output parasite
 */

// Handler AJAX
add_action( 'wp_ajax_jobiizy_load_job_details', 'jobiizy_load_job_details_handler' );
add_action( 'wp_ajax_nopriv_jobiizy_load_job_details', 'jobiizy_load_job_details_handler' );

function jobiizy_load_job_details_handler() {
	
	// Nettoyer tout output buffer
	if ( ob_get_level() ) {
		ob_end_clean();
	}
	
	// Vérifier le post_id
	if ( ! isset( $_POST['post_id'] ) ) {
		wp_send_json_error( 'No post ID provided' );
		exit;
	}
	
	$post_id = intval( $_POST['post_id'] );
	
	// Vérifier que le post existe
	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'job_listing' ) {
		wp_send_json_error( 'Invalid post' );
		exit;
	}
	
	// Récupérer les métadonnées
	$location = get_post_meta( $post_id, 'jobiizy_location', true );
	if ( empty( $location ) ) {
		$location = get_post_meta( $post_id, '_job_location', true );
	}
	
	$job_type = get_post_meta( $post_id, 'jobiizy_job_type', true );
	if ( empty( $job_type ) ) {
		$terms = get_the_terms( $post_id, 'job_listing_type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$job_type = $terms[0]->name;
		}
	}
	
	$company = get_post_meta( $post_id, '_company_name', true );
	$company_logo = get_post_meta( $post_id, '_company_logo', true );
	$company_website = get_post_meta( $post_id, '_company_website', true );
	$salary = get_post_meta( $post_id, 'jobiizy_salary', true );
	$application = get_post_meta( $post_id, 'jobiizy_application', true );
	if ( empty( $application ) ) {
		$application = get_post_meta( $post_id, '_application', true );
	}
	
	// Construire le HTML - SANS AUCUN ECHO AVANT
	ob_start();
	?>
	
	<div class="jobiizy-job-detail">
		
		<!-- Bouton en haut (visible immédiatement) -->
		<div class="jobiizy-split-cta jobiizy-cta-top">
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="button btn chrome-btn chrome-btn-filled" style="width:100%;text-align:center;" target="_blank">
				<span class="chrome-btn-glow"></span>
				<span class="chrome-btn-bg"></span>
				<span class="chrome-btn-content">Voir l'offre / Postuler</span>
			</a>
		</div>
		
		<div class="job-detail-header">
			<?php if ( $company_logo ) : ?>
				<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company ); ?>" class="company-logo-large">
			<?php endif; ?>
			
			<div class="company-info-detail">
				<?php if ( $company ) : ?>
					<h4 class="company-name-detail">
						<i class="las la-building"></i>
						<?php echo esc_html( $company ); ?>
					</h4>
				<?php endif; ?>
				
				<?php if ( $company_website ) : ?>
					<a href="<?php echo esc_url( $company_website ); ?>" target="_blank" class="company-website">
						<i class="las la-external-link-alt"></i>
						Site web
					</a>
				<?php endif; ?>
			</div>
		</div>
		
		<h2 class="job-detail-title"><?php echo get_the_title( $post_id ); ?></h2>
		
		<div class="job-detail-meta">
			<?php if ( $location ) : ?>
				<div class="meta-item">
					<i class="las la-map-marker"></i>
					<div>
						<strong>Localisation</strong>
						<span><?php echo esc_html( $location ); ?></span>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( $job_type ) : ?>
				<div class="meta-item">
					<i class="las la-clock"></i>
					<div>
						<strong>Type de contrat</strong>
						<span><?php echo esc_html( $job_type ); ?></span>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( $salary ) : ?>
				<div class="meta-item">
					<i class="las la-money-bill-wave"></i>
					<div>
						<strong>Salaire</strong>
						<span><?php echo esc_html( $salary ); ?></span>
					</div>
				</div>
			<?php endif; ?>
			
			<div class="meta-item">
				<i class="las la-calendar"></i>
				<div>
					<strong>Publié</strong>
					<span><?php echo human_time_diff( get_the_time( 'U', $post_id ), current_time( 'timestamp' ) ); ?> ago</span>
				</div>
			</div>
		</div>
		
		<div class="job-detail-description">
			<h3>Description du poste</h3>
			<?php
			$raw     = wp_strip_all_tags( get_the_content( null, false, $post_id ) );
			$excerpt = mb_substr( $raw, 0, 250 );
			if ( mb_strlen( $raw ) > 250 ) {
				$excerpt .= '…';
			}
			echo '<p>' . esc_html( $excerpt ) . '</p>';
			?>
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
			   target="_blank"
			   class="jobiizy-read-more-btn"
			   style="color:#60a5fa;font-weight:600;">
			    <i class="las la-book-open"></i>
				Lire la description complète
    			<i class="las la-arrow-right"></i>
			</a>
		</div>
		
	</div>

	<style>
	.jobiizy-job-detail {
		color: #e2e8f0;
	}

	/* Bouton en haut (toujours visible) */
	.jobiizy-cta-top {
		margin-bottom: 10px;
		padding-bottom: 20px;
		border-bottom: 1px solid rgba(100, 116, 139, 0.2);
		position: relative !important;
		bottom: auto !important;
		left: auto !important;
		right: auto !important;
		z-index: 1 !important;
	}

	/* Bouton sticky en bas (apparaît au scroll) */
	.jobiizy-cta-sticky {
		transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
		            opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
	}
	
	/* Animation initiale d'apparition */
	@keyframes slideUp {
		from {
			opacity: 0;
			transform: translateY(20px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	/* Styles communs des boutons (vos styles personnalisés) */
	.jobiizy-split-cta-blue {
		position: sticky !important;
		bottom: 0 !important;
		left: 0 !important;
		right: 0 !important;
		padding: 5px !important;
		z-index: 99 !important;
		width: 100% !important;
		backdrop-filter: blur(10px);
		border: 1px solid rgba(59, 130, 246, 0.3);
		border-radius: 9999px;
	}
	
	.jobiizy-split-cta-blue:hover {
		transform: scale(1.02);
	}

	.jobiizy-split-cta-blue .button {
		display: block !important;
		width: 100%;
		backdrop-filter: blur(10px);
		color: white !important;
		font-size: 16px;
		display: inline-flex;
		align-items: center;
		transition: all 0.3s ease;
		box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
		border: none;
		border-radius: 9999px !important;
		font-weight: 600 !important;
		text-transform: uppercase !important;
		transition: transform 0.2s ease, background 0.2s ease !important;
	}
	
	.jobiizy-split-cta-blue .button:hover {
		background: linear-gradient(135deg, #2563eb, #1d4ed8);
		box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
		backdrop-filter: drop-shadow(2px 4px 6px black);
	}
	
	.jobiizy-split-cta-blue .button i {
		font-size: 20px;
	}
	
	.job-detail-header {
		display: flex;
		align-items: center;
		/* gap: 20px;
		margin-bottom: 30px;
		padding-bottom: 20px;
		border-bottom: 1px solid rgba(100, 116, 139, 0.3);
        */
	}
	
	.company-logo-large {
		width: 80px;
		height: 80px;
		object-fit: contain;
		background: white;
		padding: 12px;
		border-radius: 16px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
	}
	
	.company-name-detail {
		color: #93c5fd;
		font-size: 18px;
		margin: 0 0 8px;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	
	.company-website {
		color: #60a5fa;
		text-decoration: none;
		font-size: 14px;
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	
	.company-website:hover {
		text-decoration: underline;
	}
	
	.job-detail-title {
		color: #f8fafc;
		font-size: 28px;
		font-weight: 800;
		margin: 0 0 30px;
		line-height: 1.3;
	}
	
	.job-detail-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 5px;
    margin-bottom: 10px;	}
	
	.meta-item {
		display: flex;
		gap: 12px;
		background: rgba(59, 130, 246, 0.1);
		border: 1px solid rgba(59, 130, 246, 0.2);
		padding: 15px;
		border-radius: 12px;
	}
	
	.meta-item i {
		color: #60a5fa;
		font-size: 24px;
		flex-shrink: 0;
	}
	
	.meta-item div {
		display: flex;
		flex-direction: column;
		gap: 4px;
	}
	
	.meta-item strong {
		color: #94a3b8;
		font-size: 12px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	.meta-item span {
		color: #e2e8f0;
		font-size: 15px;
		font-weight: 600;
	}
	
	.job-detail-description {
		/* margin-bottom: 30px;*/
	}
	
	.job-detail-description h3 {
		color: #f8fafc;
		font-size: 20px;
		margin: 0 0 20px;
		padding-bottom: 10px;
		border-bottom: 2px solid rgba(59, 130, 246, 0.3);
	}
	
	.job-detail-description p {
		color: #cbd5e1;
		line-height: 1.8;
		margin-bottom: 15px;
	}
	
	.job-detail-description ul,
	.job-detail-description ol {
		color: #cbd5e1;
		padding-left: 20px;
		margin-bottom: 15px;
	}
	
	.job-detail-description li {
		margin-bottom: 8px;
	}
	
	@media (max-width: 768px) {
		.job-detail-meta {
			grid-template-columns: 1fr;
		}
		
		.jobiizy-cta-top {
			margin-bottom: 20px;
			padding-bottom: 15px;
		}
		
		.jobiizy-split-cta-blue {
			padding: 8px !important;
		}
		
		.jobiizy-split-cta-blue .button {
			padding: 14px 24px;
			font-size: 15px;
		}
	}
	</style>
	
	<?php
	
	$html = ob_get_clean();
	
	// Envoyer UNIQUEMENT le JSON, rien d'autre
	wp_send_json_success( $html );
	exit;
}