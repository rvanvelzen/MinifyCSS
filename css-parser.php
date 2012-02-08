<?php
require 'flex_token_stream.php';
require 'parse_engine.php';
require 'css.lime.php';

class CSSScanner extends FlexScanner {
	public function executable( ) {
		return __DIR__ . '/css-scanner';
	}

	protected function processToken( Token $tt ) {
		switch( $tt->type ) {
		case 'URI':
			$tt->value = substr( $tt->value, 4, -1 );
			if ($tt->value[0] !== '"' && $tt->value[0] !== "'") {
				break;
			}
		case 'STRING':
			$qs = $tt->value[0];

			$tt->value = preg_replace_callback( '~\\\\(?|(\n)(\s*)|([\'"]))~', function( $m ) use( $qs ) {
				if ( $m[1] === '"' || $m[1] === "'" ) {
					if ( $m[1] === $qs ) {
						return $qs;
					}

					return $m[1];
				} else {
					return $m[2];
				}
			}, substr( $tt->value, 1, -1 ) );
			break;
		case 'HASH':
			$tt->value = substr( $tt->value, 1 );
			break;
		}
	}
}

class Node {
	private $type;

	public function __construct( $type, $value = null ) {
		$this->type = $type;
		if ($value !== null) {
			$this->value = $value;
		}

		if( func_num_args() > 2 ) {
			foreach( array_slice( func_get_args( ), 2 ) as $arg ) {
				if( $arg !== null ) {
					$this->addNode( $arg );
				}
			}
		}
	}

	public function __set( $name, $value ) {
		$this->$name = $value;
	}

	public function __isset( $name ) {
		return isset( $this->$name );
	}

	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		return null;
	}

	public function addNode( Node $node ) {
		if (!isset($this->nodes)) $this->nodes = array();
		$this->nodes[] = $node;
	}
}


/**
 * Dump variabelen
 *
 * @param mixed $param,... Geef er zoveel op als je wil
 * @return void
 */
function dump() {
	$args = func_get_args();
	ob_start();
	foreach ($args as $arg) {
		var_dump($arg);
	}
	$output = preg_replace(array(
		'{=>\s*}',
		'~array\(0\) {\s*}~'
	), array(
		' => ',
		'array(0) {}'
	), ob_get_clean());

	echo $output;
}


$parser = new parse_engine(new css_parser);
$stream = new CSSScanner(file_get_contents('css/design.css'));
dump($stream->feed($parser));
