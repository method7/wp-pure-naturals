<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy221_PageBase $page
 */
?>
<div class="wrio-servers">
    <div>
        <label for="wrio-change-optimization-server">
			<?php _e( 'Select optimization server:', 'robin-image-optimizer' ); ?>
            <span><?php _e( 'Please, find the list of available servers for image optimization below. If the server has a state “Down”, it means that the server is not available, and you should choose another one. “Stable” means that the server is available and you can use it.', 'robin-image-optimizer' ); ?></span>
        </label>
		<?php
		$server = WRIO_Plugin::app()->getPopulateOption( 'image_optimization_server', 'server_1' );
		?>
        <select id="wrio-change-optimization-server" class="factory-dropdown factory-from-control-dropdown form-control">
            <option value="server_1" <?php selected( $server, 'server_1' ); ?>>
				<?php echo __( 'Server 1 - image size limit up to 5 MB', 'robin-image-optimizer' ); ?>
            </option>
            <option value="server_2" <?php selected( $server, 'server_2' ); ?>>
				<?php echo __( 'Server 2 - beta', 'robin-image-optimizer' ); ?>
            </option>
            <option value="server_5" <?php selected( $server, 'server_5' ); ?>>
				<?php echo __( 'Premium - no limits', 'robin-image-optimizer' ); ?>
            </option>
        </select>
        <div class="wrio-server-status-wrap">
            <span><strong><?php echo __( 'Status', 'robin-image-optimizer' ); ?>:</strong></span>
            <span class="wrio-server-status wrio-server-check-proccess"> </span>
        </div>
        <div class="wrio-premium-user-balance-wrap" style="<?php echo $server == 'server_1' ? "display: none;" : "";  ?>">
            <span><strong><?php echo __( 'Credits', 'robin-image-optimizer' ); ?>:</strong></span>
            <span class="wrio-premium-user-balance wrio-premium-user-balance-check-proccess" data-server="<?=$server;?>" data-toggle="tooltip" title="<?=__('Only original images are limited. Thumbnails are not limited','robin-image-optimizer');?>"> </span>
        </div>
    </div>
</div>
