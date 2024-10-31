<?php
namespace Plainware;

class Layout
{
	public static function render( array $x, $app )
	{
?>

<div id="pw2">

<header>
	<?php if( isset($x['menubar']) ) : ?>
		<?php echo $app->Html->renderMenubar( $x['menubar'], $x['slug'] ); ?>
	<?php endif; ?>

	<?php if( isset($x['breadcrumb']) ) : ?>
		<?php echo $app->Html->renderBreadcrumb( $x['breadcrumb'] ); ?>
	<?php endif; ?>

	<?php if( isset($x['title']) ) : ?>
		<h1><?php echo esc_html($x['title']); ?></h1>
	<?php endif; ?>

	<?php if( isset($x['toolbar']) ) : ?>
		<nav role="toolbar">
			<?php echo $app->Html->renderMenu( $x['toolbar'] ); ?>
		</nav>
	<?php endif; ?>
</header>

[[content]]

</div>

<script>
function pwInputSelectToggle( targetClassName, val ){
	if( val !== null ){
		var all = document.getElementsByClassName( targetClassName );
		for( var ii = 0; ii < all.length; ii++ ){
			all[ii].style.display = 'none';
		}
		var show = document.getElementsByClassName( targetClassName + '-' + val );
		for( var jj = 0; jj < show.length; jj++ ){
			show[jj].style.display = 'block';
		}
	}
}

document.addEventListener( 'DOMContentLoaded', function(){
	var togglers = document.querySelectorAll( '[data-pw-toggle]' );

	for( var ii = 0; ii < togglers.length; ii++ ){
		var val = null;

		var targetClassName = togglers[ii].getAttribute('data-pw-toggle');

		if( 'button' == togglers[ii].type ){
			togglers[ii].onclick = function( e ){
				pwInputSelectToggle( e.target.getAttribute('data-pw-toggle'), e.target.value );
			}
		}
		else {
			switch( togglers[ii].type ){
				case 'checkbox':
					val = togglers[ii].checked ? 'on' : 'off';
					break;
				case 'radio':
					if( togglers[ii].checked ){
						val = togglers[ii].value;
					}
					break;
				default:
					val = togglers[ii].value;
					break;
			}

			if( ('radio' !== togglers[ii].type) || (null !== val) ){
				// var all = document.getElementsByClassName( targetClassName );
				// for( var jj = 0; jj < all.length; jj++ ){
					// all[jj].style.display = 'none';
				// }

				pwInputSelectToggle( targetClassName, val );
			}

			togglers[ii].onchange = function( e ){
				var val = null;
				if( 'checkbox' == e.target.type ){
					val = e.target.checked ? 'on' : 'off';
				}
				else if( 'radio' == e.target.type ){
					val = e.target.checked ? e.target.value : null;
				}
				else {
					val = e.target.value;
				}
				pwInputSelectToggle( e.target.getAttribute('data-pw-toggle'), val );
			}
		}
	}
});
</script>

<?php
	}
}