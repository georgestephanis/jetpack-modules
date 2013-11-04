<?php

class Jetpack_Form_Elements {

	public static function text( $args = array() ) {
		?>
		<input type="text" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $args['value'] ); ?>" />
		<?php if ( isset( $args['description'] ) ) : ?>
		<p class="description"><?php echo $args['description']; ?></p>
		<?php endif;
	}

	public static function checkbox( $args = array() ) {
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>" value="1" <?php checked( $args['value'] ); ?> />
			<?php echo esc_html( $args['label'] ); ?><br/>
		</label>
		<?php if ( isset( $args['description'] ) ) : ?>
		<p class="description"><?php echo $args['description']; ?></p>
		<?php endif;
	}

	public static function checkboxes( $args = array() ) {
		if ( empty( $args['choices'] ) )
			return;

		foreach ( $args['choices'] as $value => $label ) :
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $args['value'] ); ?>" <?php checked( $args['value'], $value ); ?> />
			<?php echo esc_html( $label ); ?><br/>
		</label>
		<?php
		endforeach;
	}

	public static function radio( $args = array() ) {
		if ( empty( $args['choices'] ) )
			return;

		foreach ( $args['choices'] as $value => $label ) :
		?>
		<label>
			<input type="radio" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $args['value'] ); ?>" <?php checked( $args['value'], $value ); ?> />
			<?php echo esc_html( $label ); ?><br/>
		</label>
		<?php
		endforeach;
	}

	public static function textarea( $args = array() ) {
		?>
		<textarea name="<?php echo esc_attr( $args['name'] ); ?>">
			<?php echo esc_textarea( $args['value'] ); ?>
		</textarea>
		<?php if ( isset( $args['description'] ) ) : ?>
		<p class="description"><?php echo $args['description']; ?></p>
		<?php endif;
	}

	public static function select( $args = array() ) {
		if ( empty( $args['choices'] ) )
			return;
		?>
		<select name="<?php echo esc_attr( $args['name'] ); ?>">
			<?php
				foreach ( $args['choices'] as $value => $label ) :
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $args['value'], $value, false ), $label );
				endforeach;
			?>
		</select>
		<?php
	}
}