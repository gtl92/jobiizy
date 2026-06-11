<?php
/**
 * Détermine si l'offre est en candidature externe
 */
/**
 * Injecte les data-attributes Jobiizy dans les listings WP Job Manager
 * (compatible AJAX, filtres, infinite scroll)
 */
add_filter(
    'job_manager_job_listing_data_attributes',
    function ( $atts, $job ) {
         //  echo "<!-- JOBIIZY job_manager_job_listing_data_attributes) -->\n";

        // Sécurité
        if ( ! $job || empty( $job->ID ) ) {
            return $atts;
        }

        // Mode candidature
        if (
            function_exists( 'jobiizy_is_external_application' )
            && jobiizy_is_external_application( $job->ID )
        ) {
            $atts['apply-mode'] = 'external';

            // Champs custom pour la popup
            $email   = get_post_meta( $job->ID, '_field_cfwjm12185', true );
            $message = get_post_meta( $job->ID, '_field_cfwjm12180', true );

            if ( $email ) {
                $atts['apply-email'] = esc_attr( $email );
            }

            if ( $message ) {
                $atts['apply-message'] = esc_attr( $message );
            }

        } else {
            $atts['apply-mode'] = 'internal';
        }

        return $atts;
    },
    10,
    2
);
/*
add_action('wp_enqueue_scripts', function () {

    // Ne jamais injecter de CSS dans les réponses AJAX/ADMIN
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $css = <<<CSS
.jobiizy-badge-external{
    display:inline-block;margin-top:6px;padding:4px 10px;font-size:12px;font-weight:600;border-radius:20px;
    background:#eef4ff;color:#1e40af;border:1px solid #c7d2fe;
}
/* Popup externe Jobiizy */
/*
.jobiizy-external-popup{position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999;display:flex;align-items:center;justify-content:center;}
.jobiizy-popup-overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);}
.jobiizy-popup-content{position:relative;background:#fff;border-radius:12px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:slideIn .3s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
.jobiizy-popup-close{position:absolute;top:15px;right:15px;background:#f3f4f6;border:0;width:36px;height:36px;border-radius:50%;font-size:24px;line-height:1;cursor:pointer;color:#6b7280;transition:all .2s;z-index:10;}
.jobiizy-popup-close:hover{background:#4f46e5;color:#fff;transform:rotate(90deg);}
.jobiizy-popup-content h2{background:#4f46e5;color:#fff;margin:0;padding:24px 30px;font-size:24px;font-weight:600;border-radius:12px 12px 0 0;}
.jobiizy-popup-body{padding:30px;}
.jobiizy-popup-body>p{color:#4b5563;line-height:1.6;margin-bottom:24px;}
.jobiizy-external-form .form-group{margin-bottom:20px;}
.jobiizy-external-form label{display:block;font-weight:600;color:#374151;margin-bottom:8px;font-size:14px;}
.jobiizy-external-form select,
.jobiizy-external-form textarea{width:100%;padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;font-size:15px;font-family:inherit;transition:all .2s;}
.jobiizy-external-form select:focus,
.jobiizy-external-form textarea:focus{outline:none;border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1);}
.jobiizy-external-form textarea{resize:vertical;min-height:120px;}
.jobiizy-submit-btn{width:100%;padding:14px 24px;background:#4f46e5;color:#fff;border:0;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all .2s;margin-top:10px;}
.jobiizy-submit-btn:hover{background:#4338ca;transform:translateY(-1px);box-shadow:0 4px 12px rgba(79,70,229,.3);}
.jobiizy-submit-btn:active{transform:translateY(0);}
@media(max-width:640px){
  .jobiizy-popup-content{width:95%;max-height:95vh;}
  .jobiizy-popup-content h2{font-size:20px;padding:20px;}
  .jobiizy-popup-body{padding:20px;}
}
CSS;
*/
/*
    // Handle “fantôme” juste pour porter le inline CSS
    wp_register_style('jobiizy-apply-inline', false);
    wp_enqueue_style('jobiizy-apply-inline');
    wp_add_inline_style('jobiizy-apply-inline', $css);
});
*/