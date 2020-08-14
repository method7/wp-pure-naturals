<?php global $woocommerce; 
?>

	<div class="term-description brand-description">

		<?php if ( $thumbnail ) : 
			if($url!=""){
				
			$ratio = get_option( 'pw_woocommerce_brands_image_list_image_size', "150:150" );

			list( $width, $height ) = explode( ':', $ratio );	
			if( $width != 'auto' )
			{
				$width= $width.'px';
			}
			if( $height !='auto' )
			{
				$height = $height.'px';
			}
			?>
				<a href="<?php echo $url;?>"><img style="width: <?php echo $width;?>;height: <?php echo $height;?>" src="<?php echo $thumbnail; ?>" alt="<?php echo $name; ?>"></a>
			<?php }
			else { ?>
				<img src="<?php echo $thumbnail; ?>" alt="<?php echo $name; ?>"/>
			<?php } ?>
		<?php endif;
		
			if(get_option('pw_woocommerce_brands_desc_list')=="yes"){ 
				if($url!=""){
				?>
					<div class="text">
						<a href="<?php echo $url;?>">
							<?php echo wpautop( wptexturize( term_description() ) ); ?>
						</a>
					</div>
				<?php }
				else {?>
					<div class="text">
						<?php echo wpautop( wptexturize( term_description() ) ); ?>
					</div>
				<?php } ?>
		<?php } ?>

	</div>