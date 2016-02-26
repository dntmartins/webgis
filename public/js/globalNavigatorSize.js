/*
 * @filesource globalNavigatorSize.js
 * \brief Recuperar as dimensões do "view port", área útil do navegador.
 * \param void
 * \return javascript Object, {'width':myWidth,'height':myHeight}
 * \public
 */
function getNavigatorViewPort()
{
	var myWidth=0,myHeight=0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myWidth = window.innerWidth;
		myHeight = window.innerHeight;
	} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
		myHeight = document.documentElement.clientHeight;
	} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myWidth = document.body.clientWidth;
		myHeight = document.body.clientHeight;
	}
	var d={'width':myWidth,'height':myHeight};
	return d;
}
