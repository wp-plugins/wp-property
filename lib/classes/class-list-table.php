<?php
/**
 * Properties List Table class.
 *
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\List_Table' ) ) {

    class List_Table extends \UsabilityDynamics\WPLT\WP_List_Table {

      /**
       * @param array $args
       */
      public function __construct( $args = array() ) {

        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'wp-property-admin-overview' );
        wp_enqueue_script( 'wpp-jquery-fancybox' );
        wp_enqueue_style( 'wpp-jquery-fancybox-css' );

        $this->args = wp_parse_args( $args, array(
          //singular name of the listed records
          'singular' => \WPP_F::property_label(),
          //plural name of the listed records
          'plural' => \WPP_F::property_label( 'plural' ),
          // Post Type
          'post_type' => 'property',
          'orderby' => 'ID',
          'order' => 'DESC',
        ) );

        //Set parent defaults
        parent::__construct( $this->args );

        add_filter( 'wplt_column_title_label', array( $this, 'get_column_title_label' ), 10, 2 );

        /* Determine if column contains numeric values */
        add_filter( 'wplt:orderby:is_numeric', array( $this, 'is_numeric_column' ), 10, 2 );

      }

      /**
       * Allows to modify WP_Query arguments
       *
       * @param array $args
       * @return array
       */
      public function filter_wp_query( $args ) {
        return apply_filters( 'wpp::all_properties::wp_query::args', $args );
      }

      /**
       * Determines if orderby values are numeric.
       *
       */
      public function is_numeric_column( $bool, $column ) {
        $types = ud_get_wp_property( 'admin_attr_fields', array() );
        if( !empty( $types[ $column ] ) && in_array( $types[ $column ], array( 'number', 'currency' ) ) ) {
          return true;
        }
        return $bool;
      }

      /**
       * @return mixed|void
       */
      public function get_columns() {
        $columns = apply_filters( 'wpp_overview_columns', array(
          'cb' => '<input type="checkbox" />',
          'title' => __( 'Title', ud_get_wp_property( 'domain' ) ),
          'status' => __( 'Status', ud_get_wp_property( 'domain' ) ),
          'property_type' => __( 'Type', ud_get_wp_property( 'domain' ) ),
          'overview' => __( 'Overview', ud_get_wp_property( 'domain' ) ),
          'created' => __( 'Added', ud_get_wp_property( 'domain' ) ),
          'modified' => __( 'Updated', ud_get_wp_property( 'domain' ) ),
          'featured' => __( 'Featured', ud_get_wp_property( 'domain' ) )
        ) );

        $meta = ud_get_wp_property( 'property_stats', array() );

        foreach( ud_get_wp_property( 'column_attributes', array() ) as $id => $slug ) {
          if( !empty( $meta[ $slug ] ) ) {
            $columns[ $slug ] = $meta[ $slug ];
          }
        }

        $columns[ 'thumbnail' ] = __( 'Thumbnail', ud_get_wp_property( 'domain' ) );

        return $columns;
      }

      /**
       * Sortable columns
       *
       * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
       */
      public function get_sortable_columns() {
        $columns = array(
          'title' => array( 'title', false ),  //true means it's already sorted
          'created' => array( 'date', false ),
          'property_type' => array( 'property_type', false ),
          'featured' => array( 'featured', false ),
          'modified' => array( 'modified', false ),
        );

        $sortable_attributes = ud_get_wp_property( 'sortable_attributes', array() );
        if( !empty( $sortable_attributes ) && is_array( $sortable_attributes ) ) {
          foreach( $sortable_attributes as $attribute ) {
            $columns[ $attribute ] = array( $attribute, false );
          }
        }

        $columns = apply_filters( 'wpp::columns::sortable', $columns );

        return $columns;
      }

      /**
       * Returns default value for column
       *
       * @param array $item
       * @param array $column_name
       * @return string
       */
      public function column_default( $item, $column_name ) {
        switch( $column_name ) {
          default:
            //Show the whole array for troubleshooting purposes
            if( isset( $item->{$column_name} ) && is_string( $item->{$column_name} ) ) {
              return apply_filters( "wpp_stat_filter_{$column_name}", $item->{$column_name} );
            } else {
              return '-';
            }
        }
      }

      /**
       * Return Property Status
       *
       * @param $post
       * @return string
       */
      public function column_status( $post ) {
        switch( $post->post_status ) {
          case 'publish':
            $status = __( 'Published', ud_get_wp_property( 'domain' ) );
            break;
          case 'pending':
            $status = __( 'Pending', ud_get_wp_property( 'domain' ) );
            break;
          case 'trash':
            $status = __( 'Trashed', ud_get_wp_property( 'domain' ) );
            break;
          case 'auto-draft':
            $status = __( 'Auto Draft', ud_get_wp_property( 'domain' ) );
            break;
          default:
            $status = apply_filters( 'wpp::column_status::custom', ucfirst( $post->post_status ) );
            break;
        }
        return $status;
      }

      /**
       * Return Created date
       *
       * @param $post
       * @return string
       */
      public function column_created( $post ) {
        return get_the_date( '', $post );
      }

      /**
       * Return Modified date
       *
       * @param $post
       * @return string
       */
      public function column_modified( $post ) {
        ;
        return get_post_modified_time( get_option( 'date_format' ), null, $post, true );
      }

      /**
       * Return Property Type
       *
       * @param $post
       * @return mixed|string
       */
      public function column_property_type( $post ) {
        $property_types = (array)ud_get_wp_property( 'property_types' );
        $type = get_post_meta( $post->ID, 'property_type', true );

        if( isset( $type ) && is_string( $type ) && is_array( $property_types ) && !empty( $property_types[ $type ] ) ) {
          $type = $property_types[ $type ];
        }

        return !empty( $type ) ? $type : '-';

      }

      /**
       * Return Overview Information
       *
       * @param $post
       * @return mixed|string
       */
      public function column_overview( $post ) {
        $data = '';
        $attributes = ud_get_wp_property( 'property_stats' );
        $stat_count = 0;
        $hidden_count = 0;
        $display_stats = array();

        foreach( $attributes as $stat => $label ) {
          $values = isset( $post->$stat ) ? $post->$stat : array( '' );
          if( !is_array( $values ) ) {
            $values = array( $values );
          }
          foreach( $values as $value ) {
            $print_values = array();
            if( empty( $value ) || strlen( $value ) > 15 ) {
              continue;
            }
            $print_values[ ] = apply_filters( "wpp_stat_filter_{$stat}", $value );
            $print_values = implode( '<br />', $print_values );
            $stat_count++;
            $stat_row_class = '';
            if( $stat_count > 5 ) {
              $stat_row_class = 'hidden wpp_overview_hidden_stats';
              $hidden_count++;
            }
            $display_stats[ $stat ] = '<li class="' . $stat_row_class . '"><span class="wpp_label">' . $label . ':</span> <span class="wpp_value">' . $print_values . '</span></li>';
          }
        }

        if( is_array( $display_stats ) && count( $display_stats ) > 0 ) {
          if( $stat_count > 5 ) {
            $display_stats[ 'toggle_advanced' ] = '<li class="wpp_show_advanced" advanced_option_class="wpp_overview_hidden_stats">' . sprintf( __( 'Toggle %1s more.', 'wpp' ), $hidden_count ) . '</li>';
          }
          $data = '<ul class="wpp_overview_column_stats wpp_something_advanced_wrapper">' . implode( '', $display_stats ) . '</ul>';
        }
        return $data;
      }

      /**
       * Return Featured
       *
       * @param $post
       * @return mixed|string
       */
      public function column_featured( $post ) {
        $data = '';
        $featured = get_post_meta( $post->ID, 'featured', true );
        $featured = !empty( $featured ) && !in_array( $featured, array( '0', 'false' ) ) ? true : false;
        if( current_user_can( 'manage_options' ) ) {
          if( $featured ) {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle wpp_is_featured' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "' value='" . __( 'Featured', ud_get_wp_property( 'domain' ) ) . "' />";
          } else {
            $data .= "<input type='button' id='wpp_feature_{$post->ID}' class='wpp_featured_toggle' nonce='" . wp_create_nonce( 'wpp_make_featured_' . $post->ID ) . "'  value='" . __( 'Add to Featured', ud_get_wp_property( 'domain' ) ) . "' />";
          }
        } else {
          $data = $featured ? __( 'Featured', ud_get_wp_property( 'domain' ) ) : '';
        }
        return $data;
      }

      /**
       * Return Thumnail
       *
       * @param $post
       * @return mixed|string
       */
      public function column_thumbnail( $post ) {

        $data = '';

        $wp_image_sizes = get_intermediate_image_sizes();
        $thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
        if( $thumbnail_id ) {
          foreach( $wp_image_sizes as $image_name ) {
            $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
            $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
          }
          $featured_image_id = $thumbnail_id;
        } else {
          $attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );
          if( $attachments ) {
            foreach( $attachments as $attachment_id => $attachment ) {
              $featured_image_id = $attachment_id;
              break;
            }
          }
        }
        if( empty( $featured_image_id ) ) {
          return $data;
        }

        $overview_thumb_type = ud_get_wp_property( 'configuration.admin_ui.overview_table_thumbnail_size' );

        if( empty( $overview_thumb_type ) ) {
          $overview_thumb_type = 'thumbnail';
        }

        $image_large_obj = wpp_get_image_link( $featured_image_id, 'large', array( 'return' => 'array' ) );
        $image_thumb_obj = wpp_get_image_link( $featured_image_id, $overview_thumb_type, array( 'return' => 'array' ) );

        if( !empty( $image_large_obj ) && !empty( $image_thumb_obj ) ) {
          $data = '<a href="' . $image_large_obj[ 'url' ] . '" class="fancybox" rel="overview_group" title="' . $post->post_title . '"><img src="' . $image_thumb_obj[ 'url' ] . '" width="' . $image_thumb_obj[ 'width' ] . '" height="' . $image_thumb_obj[ 'height' ] . '" /></a>';
        }

        return $data;
      }

      /**
       * Returns label for Title Column
       */
      public function get_column_title_label( $title, $post ) {
        $title = get_the_title( $post );
        if( empty( $title ) )
          $title = __( '(no name)' );
        return $title;
      }

      /**
       * Add Bulk Actions
       *
       * @return array
       */
      public function get_bulk_actions() {
        $actions = array();

        if( current_user_can( 'delete_wpp_property' ) ) {
          $actions[ 'untrash' ] = __( 'Restore', ud_get_wp_property( 'domain' ) );
          //$actions[ 'refresh' ] = __( 'Refresh', ud_get_wp_property( 'domain' ) );
          $actions[ 'delete' ] = __( 'Delete', ud_get_wp_property( 'domain' ) );
        }

        return apply_filters( 'wpp::all_properties::bulk_actions', $actions );

      }

      /**
       * Handle Bulk Action's request
       *
       */
      public function process_bulk_action() {

        try {

          switch( $this->current_action() ) {

            case 'untrash':
              if( empty( $_REQUEST[ 'post_ids' ] ) || !is_array( $_REQUEST[ 'post_ids' ] ) ) {
                throw new \Exception( sprintf( __( 'Invalid request: no %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              $post_ids = $_REQUEST[ 'post_ids' ];
              foreach( $post_ids as $post_id ) {
                $post_id = (int)$post_id;
                wp_untrash_post( $post_id );
              }
              $this->message = sprintf( __( 'Selected %s have been successfully restored from Trash.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              break;

            case 'delete':
              if( empty( $_REQUEST[ 'post_ids' ] ) || !is_array( $_REQUEST[ 'post_ids' ] ) ) {
                throw new \Exception( sprintf( __( 'Invalid request: no %s IDs provided.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              $post_ids = $_REQUEST[ 'post_ids' ];
              $trashed = 0;
              $deleted = 0;
              foreach( $post_ids as $post_id ) {
                $post_id = (int)$post_id;
                if( get_post_status( $post_id ) == 'trash' ) {
                  $deleted++;
                  wp_delete_post( $post_id );
                } else {
                  $trashed++;
                  wp_trash_post( $post_id );
                }
              }
              if( $trashed > 0 && $deleted > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully moved to Trash or deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } elseif( $trashed > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully moved to Trash.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } elseif( $deleted > 0 ) {
                $this->message = sprintf( __( 'Selected %s have been successfully deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label( 'plural' ) );
              } else {
                throw new \Exception( sprintf( __( 'No one %s was deleted.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ) );
              }
              break;

            default:
              //** Any custom action can be processed using action hook */
              do_action( 'wpp::all_properties::process_bulk_action', $this->current_action() );
              break;

          }

        } catch ( \Exception $e ) {
          $this->error = $e->getMessage();
        }

      }

    }

  }

}
