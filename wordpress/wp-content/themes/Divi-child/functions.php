 <?php 

  function get_rating_html( $rating = null ) {
    $rating_html = '';

    if ( ! is_numeric( $rating ) ) {
      $rating = $this->get_average_rating();
    }

    if ( $rating > 0 ) {

      $rating_html  = '<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating ) . '">';

      $rating_html .= '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%"><strong class="rating">' . $rating . '</strong> ' . __( 'out of 5', 'woocommerce' ) . '</span>';

      $rating_html .= '</div>';
    }

    return apply_filters( 'woocommerce_product_get_rating_html', $rating_html, $rating );
  }

?>