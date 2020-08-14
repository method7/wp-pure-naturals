<style type="text/css">


</style>
<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

		/**
		 * 	My Custom field for meta box
		 *
		 * @author Goran Petrovic
		 * @since 1.0
		 *
		 * @return html
		 **/


			//debug args
			//print_r( $field );

			echo !empty($field->title) ? "<label id='{$field->name}'>{$field->title}</label>": '';

			// CUSTOM HTML/CSS/JS
			echo htmlentities('

				 <div class="form-group">
			    <label for="exampleInputEmail1">Email address</label>
			    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
			    <small id="emailHelp" class="form-text text-muted">We\'ll never share your email with anyone else.</small>
			  </div>');




 		echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';
 		?>
