/**
 * @version: $Id$
 * @package: SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: http://www.Sigsiu.NET

 * @copyright Copyright (C) 2006 - 2015 Sigsiu.NET GmbH (http://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and http://sobipro.sigsiu.net/licenses.

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 * $Date$
 * $Revision$
 * $Author$
 * $HeadURL$
 */

SobiPro.jQuery( document ).ready( function ()
{
	SobiPro.jQuery( '.spCategoryChooser' ).click( function ()
	{
		var requestUrl = SobiPro.jQuery( this ).attr( 'rel' );
		SobiPro.jQuery( '#spCatsChooser' ).html( '<iframe id="spCatSelectFrame" src="' + requestUrl + '" style="width: 100%; height: 100%; border: none;"> </iframe>' );
		SobiPro.jQuery( '#spCat' ).modal();
	} );

	SobiPro.jQuery( '#spCatSelect' ).bind( 'click', function ( e )
	{
		if ( !( SobiPro.jQuery( '#SP_selectedCid' ).val() ) ) {
			return;
		}
		SobiPro.jQuery( '#selectedCatPath' ).html( SobiPro.jQuery( '#SP_selectedCatPath' ).val() );
		SobiPro.jQuery( '[name^="category.parent"]' ).val( SobiPro.jQuery( '#SP_selectedCid' ).val() );
		SobiPro.jQuery( '#categoryParentName' ).html( SobiPro.jQuery( '#SP_selectedCatName' ).val() );
	} );

	if ( SobiPro.jQuery( '#SP_categoryIconHolder' ).val() ) {
		SobiPro.jQuery( '#catIcoChooser' ).html( '<img src="' + SobiPro.jQuery( '#SP_categoryIconHolder' ).val() + '" style="max-width: 55px; max-height: 55px;" />' );
	}

	if ( SobiPro.jQuery( '[name^="category.icon"]' ).val() && SobiPro.jQuery( '[name^="category.icon"]' ).val().indexOf( 'font-' ) != -1 ) {
		var Icon = JSON.parse( SobiPro.jQuery( '[name^="category.icon"]' ).val().replace( /\\'/g, '"' ) );
		var Content = ( Icon.content != undefined ) ? Icon.content : '';
		SobiPro.jQuery( '#catIcoChooser' ).html( '<' + Icon.element + ' style="margin: 5px; padding: 5px; cursor: pointer;" class="' + Icon.class + '"">' + Content + '</' + Icon.element + '>' );
	}

	if ( SobiPro.jQuery( '#category-params-icon-font' ).val() && SobiPro.jQuery( '#category-params-icon-font' ).val().indexOf( 'font-' ) != -1 ) {
		SobiPro.jQuery( '.ctrl-add-font-class' ).removeClass( 'hide' );
	}

	SobiPro.jQuery.ajax( {
		'url': 'index.php',
		'type': 'post',
		'dataType': 'json',
		'data': {
			'option': 'com_sobipro',
			'task': 'category.iconFonts',
			'sid': SobiProSection,
			'format': 'raw',
			'tmpl': 'component',
			'method': 'xhr'
		}
	} ).done( function ( response )
	{
		SobiPro.jQuery( '#category-params-icon-font option' ).each( function ( i, e )
		{
			var Found = false;
			var Option = SobiPro.jQuery( this ).val();
			if ( Option != 0 ) {
				SobiPro.jQuery.each( response, function ( i, e )
				{
					if ( Option.indexOf( e ) != -1 ) {
						Found = true;
					}
				} );
				if ( !(Found) ) {
					SobiPro.jQuery( this ).attr( 'disabled', 'disabled' );
				}
			}
		} );
	} );

	SobiPro.jQuery( '#category-params-icon-font' ).change( function ()
	{
		if ( SobiPro.jQuery( this ).val() && SobiPro.jQuery( this ).val().indexOf( 'font-' ) != -1 ) {
			SobiPro.jQuery( '.ctrl-add-font-class' ).removeClass( 'hide' );
		}
		else {
			SobiPro.jQuery( '.ctrl-add-font-class' ).addClass( 'hide' );
		}
	} );

	SobiPro.jQuery( '#catIcoChooser' ).click( function ()
	{
		if ( SobiPro.jQuery( '#category-params-icon-font' ).val().indexOf( 'font-' ) != -1 ) {
			SobiPro.jQuery( '#spIcoChooser' ).html( '' );
			var Request = {
				'option': 'com_sobipro',
				'task': 'category.icon',
				'sid': SobiProSection,
				'format': 'raw',
				'tmpl': 'component',
				'method': 'xhr',
				'font': SobiPro.jQuery( '#category-params-icon-font' ).val()
			};
			SobiPro.jQuery.ajax( {
				url: 'index.php',
				type: 'post',
				dataType: 'json',
				data: Request
			} ).done( function ( response )
			{
				if ( response.length ) {
                    var Element;
                    var Size = 1;
                    try {
                        Size = SobiPro.jQuery( '#category-params-icon-font option:selected' ).text().match( /\((.*?)\)/ );
                        Size = parseInt( Size[1] );
                    }
                    catch ( e ) {
                        Size = 1;
                    }
                    var divheight;
                    var divwidth;
                    var paddingtop;
                    switch (Size) {
                        case 2: divheight=30; divwidth=40; paddingtop=5; break;
                        case 3: divheight=38; divwidth=58; paddingtop=10; break;
                        case 4: divheight=45; divwidth=80; paddingtop=10; break;
                        case 5: divheight=50; divwidth=100; paddingtop=15; break;
                        default: divheight=20; divwidth=30; paddingtop=3; break;
                    }
					SobiPro.jQuery.each( response, function ( i, e )
					{
						var Content = ( e.content != undefined ) ? e.content : '';
						Element = e.element;
						e.font = SobiPro.jQuery( '#category-params-icon-font' ).val();
						SobiPro.jQuery( '#spIcoChooser' )
							.append( '<div style="float:left;width:' + divwidth + 'px;line-height:' + divheight + 'px;border: solid 1px #cccccc;border-radius:4px;margin:2px;background-color:#F4F4F4;text-align:center;padding-top:' + paddingtop + 'px;"><' + e.element + ' style="cursor: pointer; margin-right:0;" class="' + e.class + '" data-setting="' + JSON.stringify( e ).replace( /"/g, "'" ) + '">' + Content + '</' + e.element + '></div>' );
					} );
					SobiPro.jQuery( '#spIcoChooser' ).find( Element ).click( function ()
					{
						SobiPro.jQuery( '#catIcoChooser' ).html( '' );
						SobiPro.jQuery( '#catIcoChooser' ).append( SobiPro.jQuery( this ).clone() );
						SobiPro.jQuery( '[name^="category.icon"]' ).val( SobiPro.jQuery( this ).data( 'setting' ) )
					} );
					SobiPro.jQuery( '#spIcoChooser').css('padding-left','22px');
					SobiPro.jQuery( '#spIco' ).modal();
				}
			} );
		}
		else {
			var requestUrl = SobiPro.jQuery( this ).attr( 'rel' );
			SobiPro.jQuery( '#spIcoChooser' ).html( '<iframe id="spIcoSelectFrame" src="' + requestUrl + '" style="height: 400px; border: none;"> </iframe>' );
			SobiPro.jQuery( '#spIco' ).modal();
		}
	} );
} );

function SPSelectIcon( src, name )
{
	SobiPro.jQuery( '#SP_categoryIconHolder' ).val( src );
	SobiPro.jQuery( '[name^="category.icon"]' ).val( name );
	if ( SobiPro.jQuery( '#SP_categoryIconHolder' ).val() ) {
		SobiPro.jQuery( '#catIcoChooser' ).html( '<img src="' + SobiPro.jQuery( '#SP_categoryIconHolder' ).val() + '" style="max-width: 55px; max-height: 55px;" />' );
	}
	SobiPro.jQuery( '#spIco' ).modal( 'hide' );
}
