<?php
defined( 'ABSPATH' ) || exit;

final class TAQL_Plugin {
	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'init', array( $this, 'register_quote_post_type' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );

		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'single_quote_button' ) );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'loop_quote_button' ), 15 );

		foreach ( array( 'add', 'update', 'remove', 'undo', 'get', 'buy', 'submit' ) as $action ) {
			add_action( 'wp_ajax_taql_' . $action, array( $this, 'ajax_' . $action ) );
			add_action( 'wp_ajax_nopriv_taql_' . $action, array( $this, 'ajax_' . $action ) );
		}

		add_filter( 'manage_taql_quote_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_taql_quote_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
		add_action( 'add_meta_boxes_taql_quote', array( $this, 'add_quote_metaboxes' ) );
		add_action( 'save_post_taql_quote', array( $this, 'save_quote_status' ) );
	}

	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', TAQL_FILE, true );
		}
	}

	public static function activate() {
		if ( ! get_option( 'taql_page_id' ) ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => 'Mi cotización',
					'post_name'    => 'mi-cotizacion',
					'post_content' => '[ta_quote_list]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				update_option( 'taql_page_id', absint( $page_id ) );
			}
		}
		self::instance()->register_quote_post_type();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public function woocommerce_notice() {
		if ( ! class_exists( 'WooCommerce' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'TA Lista de Cotización requiere WooCommerce activo.', 'ta-quote-list' ) . '</p></div>';
		}
	}

	public function register_quote_post_type() {
		register_post_type(
			'taql_quote',
			array(
				'labels' => array(
					'name'          => __( 'Cotizaciones', 'ta-quote-list' ),
					'singular_name' => __( 'Cotización', 'ta-quote-list' ),
					'menu_name'     => __( 'Cotizaciones', 'ta-quote-list' ),
					'edit_item'     => __( 'Ver cotización', 'ta-quote-list' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => class_exists( 'WooCommerce' ) ? 'woocommerce' : true,
				'capability_type'     => 'shop_order',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
				'show_in_rest'        => false,
			)
		);
	}

	public function register_shortcode() {
		add_shortcode( 'ta_quote_list', array( $this, 'render_quote_page' ) );
	}

	public function enqueue_assets() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		wp_enqueue_style( 'taql', TAQL_URL . 'assets/css/taql.css', array(), TAQL_VERSION );
		wp_enqueue_script( 'taql', TAQL_URL . 'assets/js/taql.js', array( 'jquery' ), TAQL_VERSION, true );
		wp_localize_script(
			'taql',
			'TAQL',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'taql_frontend' ),
				'listUrl'  => $this->get_list_url(),
				'cartUrl'  => wc_get_cart_url(),
				'i18n'     => array(
					'added'       => __( 'Producto agregado a la cotización.', 'ta-quote-list' ),
					'viewList'    => __( 'Ver lista', 'ta-quote-list' ),
					'error'       => __( 'No se pudo completar la acción. Intentalo nuevamente.', 'ta-quote-list' ),
					'confirmBuy'  => __( '¿Querés enviar todos los productos disponibles al carrito?', 'ta-quote-list' ),
					'confirmSend' => __( '¿Querés enviar esta solicitud de cotización?', 'ta-quote-list' ),
				),
			)
		);
	}

	private function get_list_url() {
		$page_id = absint( get_option( 'taql_page_id' ) );
		return $page_id ? get_permalink( $page_id ) : home_url( '/mi-cotizacion/' );
	}

	private function ensure_session() {
		if ( function_exists( 'WC' ) && WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	private function get_items() {
		$this->ensure_session();
		$items = WC()->session ? WC()->session->get( 'taql_items', array() ) : array();
		return is_array( $items ) ? $items : array();
	}

	private function set_items( $items ) {
		$this->ensure_session();
		WC()->session->set( 'taql_items', $items );
		WC()->session->set( 'taql_sent_to_cart', false );
	}

	private function verify_ajax() {
		check_ajax_referer( 'taql_frontend', 'nonce' );
		if ( ! class_exists( 'WooCommerce' ) || ! WC()->session ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce no está disponible.', 'ta-quote-list' ) ), 503 );
		}
		$this->ensure_session();
	}

	private function normalize_quantity( $value ) {
		$quantity = absint( $value );
		return max( 1, $quantity );
	}

	public function single_quote_button() {
		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return;
		}
		$product_id = $product->get_id();
		echo '<button type="button" class="button alt taql-add-button" data-product-id="' . esc_attr( $product_id ) . '">' . esc_html__( 'Cotizar', 'ta-quote-list' ) . '</button>';
	}

	public function loop_quote_button() {
		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() ) {
			return;
		}
		echo '<a href="#" class="button taql-add-button" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Cotizar', 'ta-quote-list' ) . '</a>';
	}

	public function ajax_add() {
		$this->verify_ajax();
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$variation_id = absint( $_POST['variation_id'] ?? 0 );
		$id = $variation_id ?: $product_id;
		$product = wc_get_product( $id );
		$parent = wc_get_product( $product_id );
		if ( $parent && $parent->is_type( 'variable' ) && ! $variation_id ) {
			wp_send_json_error( array( 'message' => __( 'Elegí las opciones del producto antes de cotizar.', 'ta-quote-list' ) ), 400 );
		}
		if ( $variation_id && ( ! $product || ! $product->is_type( 'variation' ) || $product->get_parent_id() !== $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'La variación seleccionada no es válida.', 'ta-quote-list' ) ), 400 );
		}
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => __( 'El producto no está disponible para cotizar.', 'ta-quote-list' ) ), 400 );
		}
		$quantity = $this->normalize_quantity( $_POST['quantity'] ?? 1 );
		$items = $this->get_items();
		$key = (string) $id;
		$items[ $key ] = array(
			'product_id'   => $variation_id ? $product->get_parent_id() : $product_id,
			'variation_id' => $variation_id,
			'quantity'     => isset( $items[ $key ] ) ? $this->normalize_quantity( $items[ $key ]['quantity'] + $quantity ) : $quantity,
		);
		$this->set_items( $items );
		wp_send_json_success( array( 'count' => count( $items ), 'listUrl' => $this->get_list_url() ) );
	}

	public function ajax_update() {
		$this->verify_ajax();
		$key = sanitize_key( $_POST['key'] ?? '' );
		$items = $this->get_items();
		if ( ! isset( $items[ $key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'El producto ya no está en la lista.', 'ta-quote-list' ) ), 404 );
		}
		$items[ $key ]['quantity'] = $this->normalize_quantity( $_POST['quantity'] ?? 1 );
		$this->set_items( $items );
		wp_send_json_success( array( 'html' => $this->render_list_content() ) );
	}

	public function ajax_remove() {
		$this->verify_ajax();
		$key = sanitize_key( $_POST['key'] ?? '' );
		$items = $this->get_items();
		if ( ! isset( $items[ $key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'El producto ya no está en la lista.', 'ta-quote-list' ) ), 404 );
		}
		WC()->session->set( 'taql_last_removed', array( 'key' => $key, 'item' => $items[ $key ], 'time' => time() ) );
		unset( $items[ $key ] );
		$this->set_items( $items );
		wp_send_json_success( array( 'html' => $this->render_list_content(), 'undo' => true ) );
	}

	public function ajax_undo() {
		$this->verify_ajax();
		$removed = WC()->session->get( 'taql_last_removed' );
		if ( ! is_array( $removed ) || empty( $removed['item'] ) || ( time() - absint( $removed['time'] ?? 0 ) ) > 30 ) {
			wp_send_json_error( array( 'message' => __( 'El tiempo para deshacer ya terminó.', 'ta-quote-list' ) ), 410 );
		}
		$items = $this->get_items();
		$items[ $removed['key'] ] = $removed['item'];
		$this->set_items( $items );
		WC()->session->__unset( 'taql_last_removed' );
		wp_send_json_success( array( 'html' => $this->render_list_content() ) );
	}

	public function ajax_get() {
		$this->verify_ajax();
		wp_send_json_success( array( 'html' => $this->render_list_content() ) );
	}

	public function ajax_buy() {
		$this->verify_ajax();
		$items = $this->get_items();
		$added = array();
		$failed = array();
		foreach ( $items as $key => $item ) {
			$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() || ! $product->has_enough_stock( $item['quantity'] ) ) {
				$failed[] = $product ? $product->get_name() : sprintf( __( 'Producto #%d', 'ta-quote-list' ), $item['product_id'] );
				continue;
			}
			$variation = $product->is_type( 'variation' ) ? $product->get_variation_attributes() : array();
			$result = WC()->cart->add_to_cart( $item['product_id'], $item['quantity'], $item['variation_id'], $variation );
			if ( $result ) {
				$added[] = $product->get_name();
			} else {
				$failed[] = $product->get_name();
			}
		}
		$status = empty( $failed ) ? 'complete' : ( empty( $added ) ? 'failed' : 'partial' );
		if ( ! empty( $added ) ) {
			WC()->session->set( 'taql_sent_to_cart', array( 'status' => $status, 'time' => time(), 'failed' => $failed ) );
		}
		wp_send_json_success(
			array(
				'status'  => $status,
				'added'   => $added,
				'failed'  => $failed,
				'cartUrl' => wc_get_cart_url(),
				'html'    => $this->render_list_content(),
			)
		);
	}

	public function render_quote_page() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '<p>' . esc_html__( 'WooCommerce debe estar activo para usar el cotizador.', 'ta-quote-list' ) . '</p>';
		}
		ob_start();
		echo '<div id="taql-app" class="taql-app">';
		echo $this->render_list_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		return ob_get_clean();
	}

	private function calculate_rows() {
		$rows = array();
		$subtotal = 0.0;
		$tax = 0.0;
		$discount_total = 0.0;
		foreach ( $this->get_items() as $key => $item ) {
			$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
			if ( ! $product ) {
				continue;
			}
			$quantity = $this->normalize_quantity( $item['quantity'] );
			$line_ex_tax = (float) wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
			$line_inc_tax = (float) wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
			$line_tax = max( 0, $line_inc_tax - $line_ex_tax );
			$regular = (float) $product->get_regular_price();
			$current = (float) $product->get_price();
			$regular_inc_tax = $regular > 0 ? (float) wc_get_price_including_tax( $product, array( 'qty' => $quantity, 'price' => $regular ) ) : $line_inc_tax;
			$discount = max( 0, $regular_inc_tax - $line_inc_tax );
			$rows[] = compact( 'key', 'item', 'product', 'quantity', 'line_ex_tax', 'line_inc_tax', 'line_tax', 'discount' );
			$subtotal += $line_ex_tax;
			$tax += $line_tax;
			$discount_total += $discount;
		}
		return array( 'rows' => $rows, 'subtotal' => $subtotal, 'discount' => $discount_total, 'tax' => $tax, 'total' => $subtotal + $tax );
	}

	private function render_list_content() {
		$data = $this->calculate_rows();
		ob_start();
		if ( empty( $data['rows'] ) ) {
			echo '<div class="taql-empty"><h2>' . esc_html__( 'Tu lista de cotización está vacía', 'ta-quote-list' ) . '</h2><p>' . esc_html__( 'Agregá productos con el botón “Cotizar”.', 'ta-quote-list' ) . '</p></div>';
			return ob_get_clean();
		}
		$sent = WC()->session->get( 'taql_sent_to_cart' );
		if ( is_array( $sent ) ) {
			$class = 'complete' === $sent['status'] ? 'taql-success' : 'taql-warning';
			$message = 'complete' === $sent['status'] ? __( 'Esta lista ya fue enviada a compras.', 'ta-quote-list' ) : __( 'Esta lista fue enviada parcialmente a compras.', 'ta-quote-list' );
			echo '<div class="taql-notice ' . esc_attr( $class ) . '"><strong>' . esc_html( $message ) . '</strong> <a href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'Ver carrito', 'ta-quote-list' ) . '</a></div>';
		}
		echo '<div class="taql-print-header"><h1>' . esc_html__( 'Solicitud de cotización', 'ta-quote-list' ) . '</h1><p>' . esc_html( wp_date( get_option( 'date_format' ) ) ) . '</p></div>';
		echo '<div class="taql-table-wrap"><table class="taql-table"><thead><tr>';
		foreach ( array( '', __( 'SKU', 'ta-quote-list' ), __( 'Producto', 'ta-quote-list' ), __( 'Descripción', 'ta-quote-list' ), __( 'Cantidad', 'ta-quote-list' ), __( 'Precio', 'ta-quote-list' ), __( 'Subtotal', 'ta-quote-list' ), '' ) as $heading ) {
			echo '<th>' . esc_html( $heading ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $data['rows'] as $row ) {
			$product = $row['product'];
			echo '<tr data-key="' . esc_attr( $row['key'] ) . '">';
			echo '<td class="taql-image">' . wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ) . '</td>';
			echo '<td data-label="' . esc_attr__( 'SKU', 'ta-quote-list' ) . '">' . esc_html( $product->get_sku() ?: '—' ) . '</td>';
			echo '<td data-label="' . esc_attr__( 'Producto', 'ta-quote-list' ) . '"><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a></td>';
			echo '<td data-label="' . esc_attr__( 'Descripción', 'ta-quote-list' ) . '" class="taql-description">' . wp_kses_post( wp_trim_words( $product->get_short_description(), 24 ) ) . '</td>';
			echo '<td data-label="' . esc_attr__( 'Cantidad', 'ta-quote-list' ) . '"><input class="taql-quantity" type="number" inputmode="numeric" min="1" step="1" value="' . esc_attr( $row['quantity'] ) . '" aria-label="' . esc_attr__( 'Cantidad solicitada', 'ta-quote-list' ) . '"></td>';
			echo '<td data-label="' . esc_attr__( 'Precio', 'ta-quote-list' ) . '">' . wp_kses_post( wc_price( (float) $product->get_price() ) ) . '</td>';
			echo '<td data-label="' . esc_attr__( 'Subtotal', 'ta-quote-list' ) . '">' . wp_kses_post( wc_price( $row['line_inc_tax'] ) ) . '</td>';
			echo '<td class="taql-actions"><button type="button" class="taql-remove" aria-label="' . esc_attr__( 'Quitar producto', 'ta-quote-list' ) . '">&times;</button></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '<div class="taql-summary"><div><span>' . esc_html__( 'Subtotal', 'ta-quote-list' ) . '</span><strong>' . wp_kses_post( wc_price( $data['subtotal'] ) ) . '</strong></div><div><span>' . esc_html__( 'Descuentos incluidos', 'ta-quote-list' ) . '</span><strong>' . wp_kses_post( wc_price( $data['discount'] ) ) . '</strong></div><div><span>' . esc_html__( 'Impuestos', 'ta-quote-list' ) . '</span><strong>' . wp_kses_post( wc_price( $data['tax'] ) ) . '</strong></div><div class="taql-total"><span>' . esc_html__( 'Total estimado', 'ta-quote-list' ) . '</span><strong>' . wp_kses_post( wc_price( $data['total'] ) ) . '</strong></div><small>' . esc_html__( 'Los precios son estimativos y quedarán sujetos a la confirmación del vendedor.', 'ta-quote-list' ) . '</small></div>';
		echo $this->render_customer_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="taql-main-actions"><button type="button" class="button taql-print">' . esc_html__( 'Imprimir', 'ta-quote-list' ) . '</button><button type="button" class="button taql-buy-now">' . esc_html__( 'Quiero comprar ahora', 'ta-quote-list' ) . '</button></div>';
		echo '<div class="taql-undo" hidden><span>' . esc_html__( 'Producto eliminado.', 'ta-quote-list' ) . '</span> <button type="button">' . esc_html__( 'Deshacer', 'ta-quote-list' ) . '</button></div>';
		return ob_get_clean();
	}

	private function render_customer_form() {
		$user = wp_get_current_user();
		ob_start();
		?>
		<form class="taql-form">
			<h2><?php esc_html_e( 'Solicitar atención de un vendedor', 'ta-quote-list' ); ?></h2>
			<div class="taql-form-grid">
				<label><?php esc_html_e( 'Nombre y apellido', 'ta-quote-list' ); ?> *<input type="text" name="name" required value="<?php echo esc_attr( $user->exists() ? $user->display_name : '' ); ?>"></label>
				<label><?php esc_html_e( 'Correo electrónico', 'ta-quote-list' ); ?> *<input type="email" name="email" required value="<?php echo esc_attr( $user->exists() ? $user->user_email : '' ); ?>"></label>
				<label><?php esc_html_e( 'Teléfono', 'ta-quote-list' ); ?><input type="tel" name="phone"></label>
				<label><?php esc_html_e( 'Empresa', 'ta-quote-list' ); ?><input type="text" name="company"></label>
			</div>
			<label><?php esc_html_e( 'Comentario', 'ta-quote-list' ); ?><textarea name="message" rows="4" placeholder="<?php esc_attr_e( 'Indicá plazos, cantidades futuras u otra información útil.', 'ta-quote-list' ); ?>"></textarea></label>
			<label class="taql-consent"><input type="checkbox" name="consent" value="1" required> <?php esc_html_e( 'Acepto que mis datos sean utilizados para responder esta solicitud.', 'ta-quote-list' ); ?></label>
			<button type="submit" class="button alt"><?php esc_html_e( 'Enviar cotización', 'ta-quote-list' ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public function ajax_submit() {
		$this->verify_ajax();
		$data = $this->calculate_rows();
		if ( empty( $data['rows'] ) ) {
			wp_send_json_error( array( 'message' => __( 'La lista está vacía.', 'ta-quote-list' ) ), 400 );
		}
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! $name || ! is_email( $email ) || empty( $_POST['consent'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Completá nombre, correo válido y consentimiento.', 'ta-quote-list' ) ), 400 );
		}
		$reference = 'COT-' . wp_date( 'Ymd' ) . '-' . strtoupper( wp_generate_password( 6, false, false ) );
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'taql_quote',
				'post_status' => 'publish',
				'post_title'  => $reference . ' — ' . $name,
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'No se pudo registrar la solicitud.', 'ta-quote-list' ) ), 500 );
		}
		$snapshot = array();
		foreach ( $data['rows'] as $row ) {
			$snapshot[] = array(
				'product_id'  => $row['product']->get_id(),
				'sku'         => $row['product']->get_sku(),
				'name'        => $row['product']->get_name(),
				'description' => wp_strip_all_tags( $row['product']->get_short_description() ),
				'quantity'    => $row['quantity'],
				'unit_price'  => (float) $row['product']->get_price(),
				'subtotal'    => $row['line_ex_tax'],
				'tax'         => $row['line_tax'],
				'total'       => $row['line_inc_tax'],
			);
		}
		update_post_meta( $post_id, '_taql_reference', $reference );
		update_post_meta( $post_id, '_taql_status', 'new' );
		update_post_meta( $post_id, '_taql_customer', array(
			'name' => $name,
			'email' => $email,
			'phone' => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'company' => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
			'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		) );
		update_post_meta( $post_id, '_taql_items', $snapshot );
		update_post_meta( $post_id, '_taql_totals', array( 'subtotal' => $data['subtotal'], 'discount' => $data['discount'], 'tax' => $data['tax'], 'total' => $data['total'] ) );
		$this->send_quote_emails( $post_id, $reference, $name, $email, $snapshot, $data );
		WC()->session->set( 'taql_last_submitted', array( 'reference' => $reference, 'time' => time() ) );
		wp_send_json_success( array( 'reference' => $reference, 'message' => sprintf( __( 'Solicitud %s enviada correctamente. Un vendedor se comunicará con vos.', 'ta-quote-list' ), $reference ) ) );
	}

	private function send_quote_emails( $post_id, $reference, $name, $email, $items, $data ) {
		$lines = array( sprintf( __( 'Nueva solicitud %s', 'ta-quote-list' ), $reference ), sprintf( __( 'Cliente: %s <%s>', 'ta-quote-list' ), $name, $email ), '' );
		foreach ( $items as $item ) {
			$lines[] = sprintf( '%s | SKU: %s | %d × %s', $item['name'], $item['sku'] ?: '—', $item['quantity'], wp_strip_all_tags( wc_price( $item['unit_price'] ) ) );
		}
		$lines[] = '';
		$lines[] = sprintf( __( 'Total estimado: %s', 'ta-quote-list' ), wp_strip_all_tags( wc_price( $data['total'] ) ) );
		$lines[] = admin_url( 'post.php?post=' . absint( $post_id ) . '&action=edit' );
		$subject = sprintf( __( '[%s] Nueva solicitud de cotización %s', 'ta-quote-list' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $reference );
		wp_mail( get_option( 'admin_email' ), $subject, implode( "\n", $lines ), array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
		wp_mail( $email, sprintf( __( 'Recibimos tu solicitud %s', 'ta-quote-list' ), $reference ), sprintf( __( "Hola %s:\n\nRecibimos tu solicitud de cotización. Un vendedor la revisará y se comunicará con vos.\n\nReferencia: %s", 'ta-quote-list' ), $name, $reference ) );
	}

	public function admin_columns( $columns ) {
		return array(
			'cb' => $columns['cb'],
			'title' => __( 'Referencia / Cliente', 'ta-quote-list' ),
			'taql_company' => __( 'Empresa', 'ta-quote-list' ),
			'taql_total' => __( 'Total estimado', 'ta-quote-list' ),
			'taql_status' => __( 'Estado', 'ta-quote-list' ),
			'date' => __( 'Fecha', 'ta-quote-list' ),
		);
	}

	public function admin_column_content( $column, $post_id ) {
		$customer = (array) get_post_meta( $post_id, '_taql_customer', true );
		$totals = (array) get_post_meta( $post_id, '_taql_totals', true );
		$status = get_post_meta( $post_id, '_taql_status', true );
		if ( 'taql_company' === $column ) echo esc_html( $customer['company'] ?? '—' );
		if ( 'taql_total' === $column ) echo wp_kses_post( wc_price( $totals['total'] ?? 0 ) );
		if ( 'taql_status' === $column ) echo esc_html( $this->status_labels()[ $status ] ?? $status );
	}

	private function status_labels() {
		return array( 'new' => __( 'Nueva', 'ta-quote-list' ), 'reviewing' => __( 'En revisión', 'ta-quote-list' ), 'answered' => __( 'Respondida', 'ta-quote-list' ), 'closed' => __( 'Cerrada', 'ta-quote-list' ) );
	}

	public function add_quote_metaboxes() {
		add_meta_box( 'taql_details', __( 'Detalle de la solicitud', 'ta-quote-list' ), array( $this, 'render_admin_details' ), 'taql_quote', 'normal', 'high' );
		add_meta_box( 'taql_status', __( 'Estado', 'ta-quote-list' ), array( $this, 'render_admin_status' ), 'taql_quote', 'side' );
	}

	public function render_admin_details( $post ) {
		$customer = (array) get_post_meta( $post->ID, '_taql_customer', true );
		$items = (array) get_post_meta( $post->ID, '_taql_items', true );
		$totals = (array) get_post_meta( $post->ID, '_taql_totals', true );
		echo '<h3>' . esc_html__( 'Cliente', 'ta-quote-list' ) . '</h3><p><strong>' . esc_html( $customer['name'] ?? '' ) . '</strong><br><a href="mailto:' . esc_attr( $customer['email'] ?? '' ) . '">' . esc_html( $customer['email'] ?? '' ) . '</a><br>' . esc_html( $customer['phone'] ?? '' ) . '<br>' . esc_html( $customer['company'] ?? '' ) . '</p>';
		if ( ! empty( $customer['message'] ) ) echo '<p><strong>' . esc_html__( 'Comentario:', 'ta-quote-list' ) . '</strong><br>' . nl2br( esc_html( $customer['message'] ) ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'SKU', 'ta-quote-list' ) . '</th><th>' . esc_html__( 'Producto', 'ta-quote-list' ) . '</th><th>' . esc_html__( 'Cantidad', 'ta-quote-list' ) . '</th><th>' . esc_html__( 'Precio', 'ta-quote-list' ) . '</th><th>' . esc_html__( 'Impuestos', 'ta-quote-list' ) . '</th><th>' . esc_html__( 'Total', 'ta-quote-list' ) . '</th></tr></thead><tbody>';
		foreach ( $items as $item ) {
			echo '<tr><td>' . esc_html( $item['sku'] ?: '—' ) . '</td><td>' . esc_html( $item['name'] ) . '</td><td>' . esc_html( $item['quantity'] ) . '</td><td>' . wp_kses_post( wc_price( $item['unit_price'] ) ) . '</td><td>' . wp_kses_post( wc_price( $item['tax'] ) ) . '</td><td>' . wp_kses_post( wc_price( $item['total'] ) ) . '</td></tr>';
		}
		echo '</tbody><tfoot><tr><th colspan="5">' . esc_html__( 'Total estimado', 'ta-quote-list' ) . '</th><th>' . wp_kses_post( wc_price( $totals['total'] ?? 0 ) ) . '</th></tr></tfoot></table>';
	}

	public function render_admin_status( $post ) {
		wp_nonce_field( 'taql_save_status', 'taql_status_nonce' );
		$current = get_post_meta( $post->ID, '_taql_status', true );
		echo '<select name="taql_status" style="width:100%">';
		foreach ( $this->status_labels() as $value => $label ) echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		echo '</select>';
	}

	public function save_quote_status( $post_id ) {
		if ( ! isset( $_POST['taql_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['taql_status_nonce'] ) ), 'taql_save_status' ) || ! current_user_can( 'edit_post', $post_id ) ) return;
		$status = sanitize_key( $_POST['taql_status'] ?? '' );
		if ( isset( $this->status_labels()[ $status ] ) ) update_post_meta( $post_id, '_taql_status', $status );
	}
}
