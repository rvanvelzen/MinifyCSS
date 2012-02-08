<?php
define('TOKEN_END', 'EOF');

abstract class FlexScanner {
	protected $line;

	protected $stream;
	protected $pointer;

	protected $tokens = array( );
	protected $tokenIndex = 0;
	protected $lookahead = 0;

	abstract function executable( );

	public function __construct( $string ) {
		$scanner = $this->executable( );

		$descriptor = array( array( 'pipe', 'r' ), array( 'pipe', 'w' ) );

		$process = proc_open( $this->executable( ), $descriptor, $pipes );

		fwrite( $pipes[0], $string );
		fclose( $pipes[0] );

		$this->stream = stream_get_contents( $pipes[1] );

		fclose( $pipes[1] );
		proc_close( $process );

		$this->line = 1;
	}

	public function get( ) {
		while( $this->lookahead ) {
			--$this->lookahead;
			$this->tokenIndex = ( $this->tokenIndex + 1 ) & 3;
			$token = $this->tokens[$this->tokenIndex];

			return $token->type;
		}

		$tt = null;
		while ($tt === null) {
			if( false !== $pos = strpos( $this->stream, "\0", $this->pointer ) ) {
				$token = substr( $this->stream, $this->pointer, $pos - $this->pointer );
				$this->pointer = $pos + 1;

				list( $this->line, $tt, $value ) = explode( "\1", $token );
			} else {
				$tt = TOKEN_END;
				$value = '';
			}

			$this->tokenIndex = ( $this->tokenIndex + 1 ) & 3;

			if( !isset( $this->tokens[$this->tokenIndex] ) ) {
				$this->tokens[$this->tokenIndex] = new Token( );
			}

			$token = $this->tokens[$this->tokenIndex];
			$token->type = $tt;
			$token->value = $value;
			$token->line = $this->line;

			$this->processToken( $token );

			$tt = $token->type;
		}

		return $tt;
	}

	public function isDone( ) {
		return $this->peek( ) === TOKEN_END;
	}

	public function match( $tt ) {
		$t = $this->get( );
		foreach( func_get_args( ) as $tt ) {
			if ($t === $tt) {
				return $this->currentToken();
			}
		}

		$this->unget( );
		return false;
	}

	protected function processToken( Token $tt ) { }

	public function mustMatch($tt) {
		$t = $this->get( );
		foreach( $m = func_get_args( ) as $tt ) {
			if ($t === $tt) {
				return $this->currentToken( );
			}
		}

		throw $this->newSyntaxError('Unexpected ' . $t . '; ' . implode(' or ', $m) . ' expected');
	}

	public function token( $tt ) {
		return call_user_func_array( array( $this, 'match' ), array_map( function( $tt ) {
			return "'{$tt}'";
		}, func_get_args(  ) ) );
	}

	public function mustToken( $tt ) {
		return call_user_func_array( array( $this, 'mustMatch' ), array_map( function( $tt ) {
			return "'{$tt}'";
		}, func_get_args(  ) ) );
	}

	public function peek() {
		if( $this->lookahead ) {
			$next = $this->tokens[( $this->tokenIndex + $this->lookahead ) & 3];
			$tt = $next->type;
		} else {
			$tt = $this->get( );
			$this->unget( );
		}

		return $tt;
	}

	public function currentToken( ) {
		if( !empty( $this->tokens ) ) {
			return $this->tokens[$this->tokenIndex];
		}
	}

	public function unget( ) {
		if( ++$this->lookahead === 4 ) {
			throw $this->newSyntaxError( 'PANIC: too much lookahead!' );
		}

		$this->tokenIndex = ( $this->tokenIndex - 1 ) & 3;
	}

	public function line( ) {
		return $this->line;
	}

	public function newSyntaxError( $m ) {
		return new Exception( 'Parse error: ' . $m . ' in line ' . $this->line );
	}

    public function feed($parser) {
        while ($this->get() !== TOKEN_END) {
        	$current = $this->currentToken();
            $parser->eat($current->type, $current->value, $current->line);
        }

        return $parser->eat_eof();
    }
}

class Token {
	public $type;
	public $value;
	public $line;
}
