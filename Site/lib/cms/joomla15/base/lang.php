<?php
/**
 * @version: $Id$
 * @package: SobiPro Library
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: http://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2015 Sigsiu.NET GmbH (http://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and http://sobipro.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * $Date$
 * $Revision$
 * $Author$
 * $HeadURL$
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
require_once dirname( __FILE__ ) . '/../../joomla_common/base/lang.php';
/**
 * @author Radek Suski
 * @version 1.0
 * @created 20-Jun-2009 19:56:57
 */
final class SPLang extends SPJoomlaLang
{
	protected function _txt()
	{
		$a = func_get_args();
		$m = call_user_func_array( array( 'parent', '_txt' ), $a );
		return str_replace( '_QQ_', '"', $m );
	}

	public static function nid( $txt )
	{
		return trim( /*strtolower*/( str_replace( '__', '_', preg_replace( '/[^a-z0-9\_]/i', '_', preg_replace( '/\W/', '-', $txt ) ) ) ) );
	}

}
