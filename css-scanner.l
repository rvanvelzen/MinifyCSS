%{
	/**
	 * All rules literally taken from the CSS3 specifications.
	 */

	void out( char* t, char* v );
	void lit( );
	void tok( char* t );
%}

%option main
%option yylineno
%option case-insensitive

h                        [0-9a-f]
nonascii                 [\200-\377]
unicode                  \\{h}{1,6}[ \t\r\n\f]?
escape                   {unicode}|\\[ -~\200-\377]
nmstart                  [a-z]|{nonascii}|{escape}
nmchar                   [a-z0-9_-]|{nonascii}|{escape}
string1                  \"([\t !#$%&(-~]|\\{nl}|\'|{nonascii}|{escape})*\"
string2	                 \'([\t !#$%&(-~]|\\{nl}|\"|{nonascii}|{escape})*\'

ident                    [-]?{nmstart}{nmchar}*
name                     {nmchar}+
num                      [0-9]+|[0-9]*"."[0-9]+
string                   {string1}|{string2}
url                      ([!#$%&*-~]|{nonascii}|{escape})+
w                        [ \t\r\n\f]*
nl                       \n|\r\n|\r|\f
range                    \?{1,6}|{h}(\?{0,5}|{h}(\?{0,4}|{h}(\?{0,3}|{h}(\?{0,2}|{h}(\??|{h})))))
comment                  \/\*[^*]*\*+([^/][^*]*\*+)*\/

%%

{comment}                /* ignore whitespace and comments */
[ \t\r\n\f]              tok( "S" );

"<!--"                   tok( "CDO" );
"-->"                    tok( "CDC" );

"~="                     tok( "INCLUDES" );
"|="                     tok( "DASHMATCH" );
"^="                     tok( "PREFIXMATCH" );
"$="                     tok( "SUFFIXMATCH" );
"*="                     tok( "SUBSTRINGMATCH" );

{string}                 tok( "STRING" );

ONLY                     tok( "ONLY" );
NOT                      tok( "NOT" );
AND                      tok( "AND" );

{ident}                  tok( "IDENT" );

"#"{name}                tok( "HASH" );

"@import"                tok( "IMPORT_SYM" );
"@page"                  tok( "PAGE_SYM" );
"@media"                 tok( "MEDIA_SYM" );
"@font-face"             tok( "FONT_FACE_SYM" );
"@charset"               tok( "CHARSET_SYM" );
"@namespace"             tok( "NAMESPACE_SYM" );

"!"{w}important          tok( "IMPORTANT_SYM" );

{num}em                  tok( "EMS" );
{num}ex                  tok( "EXS" );
{num}px                  tok( "LENGTH" );
{num}cm                  tok( "LENGTH" );
{num}mm                  tok( "LENGTH" );
{num}in                  tok( "LENGTH" );
{num}pt                  tok( "LENGTH" );
{num}pc                  tok( "LENGTH" );
{num}deg                 tok( "ANGLE" );
{num}rad                 tok( "ANGLE" );
{num}grad                tok( "ANGLE" );
{num}ms                  tok( "TIME" );
{num}s                   tok( "TIME" );
{num}Hz                  tok( "FREQ" );
{num}kHz                 tok( "FREQ" );
{num}DPI                 tok( "RESOLUTION" );
{num}DPCM                tok( "RESOLUTION" );
{num}{ident}             tok( "DIMEN" );
{num}%                   tok( "PERCENTAGE" );
{num}                    tok( "NUMBER" );

"url("{w}{string}{w}")"  tok( "URI" );
"url("{w}{url}{w}")"     tok( "URI" );
{ident}"("               tok( "FUNCTION" );

#\+{range}               tok( "UNICODERANGE" );
#U\+{h}{1,6}-{h}{1,6}    tok( "UNICODERANGE" );

":not("                  tok( "NOT" );

.                        lit( );

%%

void lit( ) {
	char lit[] = "'.'";
	lit[1] = *yytext;
	out( lit, yytext );
}

void tok( char* t ) {
	out( t, yytext );
}

void out( char* type, char* value ) {
	printf( "%d\001%s\001%s", yylineno, type, value );
	fputc( 0, stdout );
}
