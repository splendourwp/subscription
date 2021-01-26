<?php
$class = isset( $note->meta[ 'comment_status' ] ) ? implode( $note->meta[ 'comment_status' ] ) : get_comment_meta( $note->id , 'sumo_status' , true ) ;
?>
<li rel="<?php echo absint( $note->id ) ; ?>" class="<?php echo '' !== $class ? $class : 'pending' ; ?>">
    <div class="note_content">
        <?php echo wpautop( wptexturize( wp_kses_post( $note->content ) ) ) ; ?>
    </div>
    <p class="meta">
        <abbr class="exact-date" title="<?php echo sumo_display_subscription_date( $note->date_created ) ; ?>"><?php echo sumo_display_subscription_date( $note->date_created ) ; ?></abbr>
        <?php printf( ' ' . __( 'by %s' , 'sumosubscriptions' ) , $note->added_by ) ; ?>
        <a href="#" class="sumo_delete_note delete_note"><?php _e( 'Delete note' , 'sumosubscriptions' ) ; ?></a>
    </p>
</li>