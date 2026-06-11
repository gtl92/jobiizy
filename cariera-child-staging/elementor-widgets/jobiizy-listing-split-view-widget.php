<?php
/**
 * CUSTOM ELEMENTOR WIDGET - JOBIIZY LISTING SPLIT VIEW
 * Version personnalisée basée sur Cariera Listing Split View
 *
 * @since   1.0.0
 * @version 1.0.0
 **/

namespace Cariera_Child\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jobiizy_Listing_Split_View extends \Elementor\Widget_Base {

	/**
	 * Get widget's name.
	 */
	public function get_name() {
		return 'jobiizy_listing_split_view';
	}

	/**
	 * Get widget's title.
	 */
	public function get_title() {
		return esc_html__( 'JobiiZy Split-View', 'cariera' );
	}

	/**
	 * Get widget's icon.
	 */
	public function get_icon() {
		return 'eicon-off-canvas';
	}

	/**
	 * Get widget's categories.
	 */
	public function get_categories() {
		return [ 'cariera-elements' ];
	}

	/**
	 * Register the controls for the widget
	 */
	protected function register_controls() {

		// SECTION CONTENT
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'cariera' ),
			]
		);

		$this->add_control(
			'listing_type',
			[
				'label'       => esc_html__( 'Listing Type', 'cariera' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'job_listing' => esc_html__( 'Job Listings', 'cariera' ),
					'resume'      => esc_html__( 'Resumes', 'cariera' ),
					'company'     => esc_html__( 'Companies', 'cariera' ),
				],
				'default'     => 'job_listing',
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'       => esc_html__( 'Items per Page', 'cariera' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '10',
				'description' => esc_html__( 'How many items to show in the job board.', 'cariera' ),
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'       => esc_html__( 'Order by', 'cariera' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'featured'      => esc_html__( 'Featured', 'cariera' ),
					'date'          => esc_html__( 'Date', 'cariera' ),
					'ID'            => esc_html__( 'ID', 'cariera' ),
					'author'        => esc_html__( 'Author', 'cariera' ),
					'title'         => esc_html__( 'Title', 'cariera' ),
					'modified'      => esc_html__( 'Modified', 'cariera' ),
					'rand'          => esc_html__( 'Random', 'cariera' ),
					'rand_featured' => esc_html__( 'Random Featured', 'cariera' ),
				],
				'default'     => 'featured',
			]
		);

		$this->add_control(
			'order',
			[
				'label'       => esc_html__( 'Order', 'cariera' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'DESC' => esc_html__( 'Descending', 'cariera' ),
					'ASC'  => esc_html__( 'Ascending', 'cariera' ),
				],
				'default'     => 'DESC',
			]
		);

		$this->add_control(
			'featured',
			[
				'label'       => esc_html__( 'Featured', 'cariera' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => [
					'default' => esc_html__( 'Default', 'cariera' ),
					'show'    => esc_html__( 'Show', 'cariera' ),
					'hide'    => esc_html__( 'Hide', 'cariera' ),
				],
				'default'     => 'default',
			]
		);

		$this->end_controls_section();

		// SECTION STYLE - NOUVEAU
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'JobiiZy Style', 'cariera' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'use_modern_design',
			[
				'label'        => esc_html__( 'Modern Design', 'cariera' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'cariera' ),
				'label_off'    => esc_html__( 'No', 'cariera' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Enable modern blue gradient design with hover effects', 'cariera' ),
			]
		);

		$this->add_control(
			'card_background',
			[
				'label'     => esc_html__( 'Card Background', 'cariera' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2d3748',
				'selectors' => [
					'{{WRAPPER}} .jobiizy-modern-card' => 'background-color: {{VALUE}}',
				],
				'condition' => [
					'use_modern_design' => 'yes',
				],
			]
		);

		$this->add_control(
			'card_border_color',
			[
				'label'     => esc_html__( 'Card Border', 'cariera' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(100, 116, 139, 0.3)',
				'selectors' => [
					'{{WRAPPER}} .jobiizy-modern-card' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'use_modern_design' => 'yes',
				],
			]
		);

		$this->add_control(
			'hover_lift',
			[
				'label'        => esc_html__( 'Hover Lift Effect', 'cariera' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'cariera' ),
				'label_off'    => esc_html__( 'No', 'cariera' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'use_modern_design' => 'yes',
				],
			]
		);

		$this->add_control(
			'hover_glow',
			[
				'label'        => esc_html__( 'Hover Glow Effect', 'cariera' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'cariera' ),
				'label_off'    => esc_html__( 'No', 'cariera' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'use_modern_design' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_shine_effect',
			[
				'label'        => esc_html__( 'Shine Animation', 'cariera' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'cariera' ),
				'label_off'    => esc_html__( 'No', 'cariera' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'use_modern_design' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get Style Dependency
	 */
	public function get_style_depends() {
		return [ 'cariera-listing-split-view', 'jobiizy-modern-cards' ];
	}

	/**
	 * Script Dependecy
	 */
	public function get_script_depends() {
		return [ 'cariera-listing-split-view' ];
	}

	/**
	 * Widget output
	 */
	protected function render() {
		$settings = $this->get_settings();

		// Enqueue styles
		wp_enqueue_style( 'cariera-listing-half-detail' );
		wp_enqueue_script( 'cariera-listing-half-detail' );

		if ( 'job_listing' === $settings['listing_type'] ) {
			wp_enqueue_style( 'cariera-single-job-listing' );
		}

		// Add custom class if modern design is enabled
		$wrapper_class = '';
		if ( 'yes' === $settings['use_modern_design'] ) {
			$wrapper_class = 'jobiizy-modern-split-view';
			
			if ( 'yes' === $settings['hover_lift'] ) {
				$wrapper_class .= ' has-hover-lift';
			}
			if ( 'yes' === $settings['hover_glow'] ) {
				$wrapper_class .= ' has-hover-glow';
			}
			if ( 'yes' === $settings['show_shine_effect'] ) {
				$wrapper_class .= ' has-shine-effect';
			}
		}

		// Prepare shortcode attributes
		$per_page = ! empty( $settings['per_page'] ) ? 'per_page="' . $settings['per_page'] . '"' : '';
		$orderby  = ! empty( $settings['orderby'] ) ? 'orderby="' . $settings['orderby'] . '"' : '';
		$order    = ! empty( $settings['order'] ) ? 'order="' . $settings['order'] . '"' : '';

		if ( 'default' === $settings['featured'] ) {
			$featured = '';
		} elseif ( 'show' === $settings['featured'] ) {
			$featured = 'featured="true"';
		} else {
			$featured = 'featured="false"';
		}

		echo '<div class="' . esc_attr( $wrapper_class ) . '">';

		// Chemin direct vers le template JobiiZy (AVEC UNDERSCORE)
		$custom_template = get_stylesheet_directory() . '/cariera_core/elements/listing-half/jobiizy-main-details.php';
		
		// Préparer les variables pour le template
		$wrapper_class_param = $wrapper_class;
		
		// Debug
		if ( file_exists( $custom_template ) ) {
			echo '<!-- ✅ JobiiZy Template FOUND: ' . esc_html( str_replace( ABSPATH, '', $custom_template ) ) . ' -->';
			
			// Charger le template JobiiZy custom
			include $custom_template;
			
		} else {
			echo '<!-- ❌ JobiiZy Template NOT FOUND -->';
			echo '<!-- Searched: ' . esc_html( $custom_template ) . ' -->';
			echo '<!-- get_stylesheet_directory(): ' . esc_html( get_stylesheet_directory() ) . ' -->';
			
			// Essayer avec un chemin alternatif
			$alt_template = WP_CONTENT_DIR . '/themes/cariera-child/cariera-core/elements/listing-half/jobiizy-main-details.php';
			
			if ( file_exists( $alt_template ) ) {
				echo '<!-- ✅ Found with WP_CONTENT_DIR: ' . esc_html( str_replace( ABSPATH, '', $alt_template ) ) . ' -->';
				include $alt_template;
			} elseif ( function_exists( 'cariera_get_template' ) ) {
				echo '<!-- Using Cariera fallback template -->';
				
				// Fallback sur le template Cariera
				cariera_get_template(
					'elements/listing-half/main-details.php',
					[
						'settings'        => $settings,
						'per_page'        => $per_page,
						'orderby'         => $orderby,
						'order'           => $order,
						'featured'        => $featured,
						'wrapper_class'   => $wrapper_class,
						'count_jobs'      => wp_count_posts( 'job_listing', 'readable' ),
					]
				);
			} else {
				// Fallback: render custom
				$this->render_custom_template( $settings, $per_page, $orderby, $order, $featured );
			}
		}

		echo '</div>';
	}

	/**
	 * Custom template rendering (fallback)
	 */
	private function render_custom_template( $settings, $per_page, $orderby, $order, $featured ) {
		$args = [
			'post_type'           => $settings['listing_type'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $settings['per_page'],
			'orderby'             => $settings['orderby'],
			'order'               => $settings['order'],
		];

		// Featured filter
		if ( 'show' === $settings['featured'] ) {
			$args['meta_query'] = [
				[
					'key'     => '_featured',
					'value'   => '1',
					'compare' => '=',
				],
			];
		} elseif ( 'hide' === $settings['featured'] ) {
			$args['meta_query'] = [
				[
					'key'     => '_featured',
					'value'   => '1',
					'compare' => '!=',
				],
			];
		}
		
		// ========================================
		// FILTRES GET - JobiiZy Popup Filters
		// ========================================
		
		// Initialiser meta_query si nécessaire
		if ( ! isset( $args['meta_query'] ) ) {
			$args['meta_query'] = [];
		}
		
		// Filtre : Mots-clés (recherche dans titre et contenu)
		if ( ! empty( $_GET['search_keywords'] ) ) {
			$args['s'] = sanitize_text_field( $_GET['search_keywords'] );
		}
		
		// Filtre : Localisation
		if ( ! empty( $_GET['search_location'] ) ) {
			$args['meta_query'][] = [
				'key'     => '_job_location',
				'value'   => sanitize_text_field( $_GET['search_location'] ),
				'compare' => 'LIKE',
			];
		}
		
		// Filtre : Catégories
		if ( ! empty( $_GET['search_categories'] ) ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'job_listing_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $_GET['search_categories'] ),
				],
			];
		}
		
		// Filtre : Types de contrat
		if ( ! empty( $_GET['filter_job_type'] ) ) {
			$job_types = is_array( $_GET['filter_job_type'] ) 
				? array_map( 'sanitize_text_field', $_GET['filter_job_type'] )
				: explode( ',', sanitize_text_field( $_GET['filter_job_type'] ) );
			
			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = [];
			}
			
			$args['tax_query'][] = [
				'taxonomy' => 'job_listing_type',
				'field'    => 'slug',
				'terms'    => $job_types,
			];
		}
		
		// Combiner tax_query avec AND si plusieurs filtres taxonomie
		if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) :
			echo '<div class="jobiizy-split-view-wrapper">';
			echo '<div class="jobiizy-listings-column">';

			while ( $query->have_posts() ) : $query->the_post();
				$this->render_job_card( get_the_ID() );
			endwhile;

			echo '</div>';
			echo '<div class="jobiizy-detail-column">';
			echo '<div class="jobiizy-detail-placeholder">';
			echo '<p>' . esc_html__( 'Select a job to view details', 'cariera' ) . '</p>';
			echo '</div>';
			echo '</div>';
			echo '</div>';

			wp_reset_postdata();
		else :
			echo '<p>' . esc_html__( 'No listings found.', 'cariera' ) . '</p>';
		endif;
	}

	/**
	 * Render individual job card
	 */
	private function render_job_card( $post_id ) {
		$location    = get_post_meta( $post_id, 'jobiizy_location', true );
		$job_type    = get_post_meta( $post_id, 'jobiizy_job_type', true );
		$is_featured = get_post_meta( $post_id, 'jobiizy_featured', true );
		$company     = get_post_meta( $post_id, '_company_name', true );

		?>
		<article class="jobiizy-modern-card job-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			
			<?php if ( $is_featured ) : ?>
				<span class="featured-badge">
					<i class="las la-star"></i>
					<?php esc_html_e( 'Featured', 'cariera' ); ?>
				</span>
			<?php endif; ?>

			<div class="job-card-content">
				
				<h3 class="job-title">
					<a href="<?php the_permalink(); ?>">
						<?php the_title(); ?>
					</a>
				</h3>

				<?php if ( $company ) : ?>
					<div class="company-name">
						<i class="las la-building"></i>
						<?php echo esc_html( $company ); ?>
					</div>
				<?php endif; ?>

				<div class="job-meta">
					<?php if ( $location ) : ?>
						<span class="job-location">
							<i class="las la-map-marker"></i>
							<?php echo esc_html( $location ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $job_type ) : ?>
						<span class="job-type">
							<i class="las la-clock"></i>
							<?php echo esc_html( $job_type ); ?>
						</span>
					<?php endif; ?>

					<span class="job-date">
						<i class="las la-calendar"></i>
						<?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ); ?> ago
					</span>
				</div>

			</div>

		</article>
		<?php
	}
}