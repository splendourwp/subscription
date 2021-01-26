<ul class="subscription_notes">
    <?php
    foreach ( $notes as $note ) {
        include( 'html-subscription-note.php' ) ;
    }
    ?>
</ul>
<div class="add_subscription_note">
    <h4>
        <?php _e( 'Add note' , 'sumosubscriptions' ) ; ?>
    </h4>
    <p>
        <textarea type="text" name="add_subscription_note" id="add_subscription_note" class="input-text" cols="20" rows="5"></textarea>
    </p>
    <p>
        <a href="#" class="sumo_add_note button" data-id="<?php echo $post->ID ; ?>"><?php _e( 'Add' , 'sumosubscriptions' ) ; ?></a>
    </p>
</div>