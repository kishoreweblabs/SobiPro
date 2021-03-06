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

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
/**
 * @author Radek Suski
 * @version 1.0
 * @created 05-Mar-2011 14:08:25
 */
class SPJoomlaInstaller
{
	protected $error = null;
	protected $errorType = null;
	protected $id = null;
	/**
	 * Enter description here ...
	 * @var DOMDocument
	 */
	protected $definition = null;
	private $c = 0;

	public function __construct()
	{
	}

	public function remove( $def )
	{
		$name = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$type = $def->getElementsByTagName( 'type' )->item( 0 )->nodeValue;
		$eid = $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue;
		switch ( $type ) {
			case 'module':
				$result = $this->removeModule( $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue );
				break;
			case 'plugin':
				$result = $this->removePlugin( $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue );
				break;
		}
		if ( $result ) {
			SPFactory::db()->delete( 'spdb_plugins', array( 'pid' => $eid, 'type' => $type ), 1 );
			return Sobi::Txt( 'CMS_EXT_REMOVED', $name );
		}
		return array( 'msg' => Sobi::Txt( 'CMS_EXT_NOT_REMOVED', $name ), 'msgtype' => 'error' );
	}

	public function removeModule( $module )
	{
		$id = SPFactory::db()->select( 'id', '#__modules', array( 'module' => $module ) )->loadResult();
		if ( $id ) {
			if ( $this->removeExt( 'module', $id ) ) {
				SPFactory::db()->delete( 'spdb_plugins', array( 'pid' => $module ) );
				return true;
			}
		}
		return false;
	}

	public function removePlugin( $plugin )
	{
		$pluginArr = explode( '_', $plugin, 2 );
		$id = SPFactory::db()->select( 'id', '#__plugins', array( 'folder' => $pluginArr[ 0 ], 'element' => $pluginArr[ 1 ] ) )->loadResult();
		if ( $id ) {
			if ( $this->removeExt( 'plugin', $id ) ) {
				SPFactory::db()->delete( 'spdb_plugins', array( 'pid' => $plugin ) );
				return true;
			}
		}
		return false;
	}

	protected function removeExt( $type, $id )
	{
		jimport( 'joomla.installer.installer' );
		return JInstaller::getInstance()->uninstall( $type, $id );
	}

	public function install( $def, $files, $dir )
	{
		switch ( $def->documentElement->getAttribute( 'type' ) ) {
			case 'language':
				$msg = $this->installLanguage( $def, $dir );
				break;
			default:
				$msg = $this->installExt( $def, $dir );
				break;
		}
		if ( $this->error ) {
			Sobi::Error( 'LangInstaller', $this->error, SPC::NOTICE, 0 );
			$msg = array( 'msg' => $this->error, 'msgtype' => $this->errorType );
		}
		return $msg;
	}

	/**
	 * @param DOMDocument $def
	 * @param string $dir
	 * @return array | string
	 */
	protected function installExt( $def, $dir )
	{
		$this->checkRequirements( $def );
		jimport( 'joomla.installer.installer' );
		jimport( 'joomla.installer.helper' );
		$installer = JInstaller::getInstance();
		$type = JInstallerHelper::detectType( $dir );
		$xp = new DOMXPath( $def );
		try {
			$installer->install( $dir );
			// it was core update - break now
			if ( $type == 'component' ) {
				SPFactory::cache()->cleanAll();
				return array( 'msg' => Sobi::Txt( 'CMS_SOBIPRO_UPDATE_INSTALLED', $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue ), 'msgtype' => SPC::SUCCESS_MSG );
			}
			$msg = Sobi::Txt( 'CMSEX_INSTALLED', $type, $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue );
			$this->id = SPLang::nid( $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue );
			$id = $xp->query( '//filename[@module|@plugin]' )->item( 0 );
			$this->id = strlen( $id->getAttribute( 'module' ) ) ? $id->getAttribute( 'module' ) : $id->getAttribute( 'plugin' );
			if ( strlen( $def->documentElement->getAttribute( 'group' ) ) ) {
				$this->id = $def->documentElement->getAttribute( 'group' ) . '_' . $this->id;
			}
			if ( $this->id ) {
				$this->definition = new DOMDocument();
				$this->definition->formatOutput = true;
				$this->definition->preserveWhiteSpace = false;
				$this->definition->appendChild( $this->definition->createElement( 'SobiProApp' ) );
				$root = $this->definition->getElementsByTagName( 'SobiProApp' )->item( 0 );
				$root->appendChild( $this->definition->createElement( 'id', $this->id ) );
				$root->appendChild( $this->definition->createElement( 'type', $type ) );
				$root->appendChild( $this->definition->createElement( 'name', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) );
				$root->appendChild( $this->definition->createElement( 'uninstall', 'cms.base.installer:remove' ) );
				$this->definition->appendChild( $root );
				$dir = SPLoader::dirPath( 'etc.installed.' . $type . 's', 'front', false );
				if ( !( SPFs::exists( $dir ) ) ) {
					SPFs::mkdir( $dir );
				}
				$path = $dir . '/' . $this->id . '.xml';
				$file = SPFactory::Instance( 'base.fs.file', $path );
				$this->definition->normalizeDocument();
				$file->content( $this->definition->saveXML() );
				$file->save();
				$this->storeData( $type, $def );
			}
			return array( 'msg' => $msg, 'msgtype' => SPC::SUCCESS_MSG );
		} catch ( Exception $x ) {
			$this->error = Sobi::Txt( 'CMS_EXT_NOT_INSTALLED' ) . ' ' . $x->getMessage();
			$this->errorType = SPC::ERROR_MSG;
			return array( 'msg' => $this->error, 'msgtype' => SPC::ERROR_MSG );
		}
	}

	protected function storeData( $type, $def )
	{
		SPFactory::db()->insertUpdate(
			'spdb_plugins',
			array(
				'pid' => $this->id,
				'name' => $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue,
				'version' => $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue,
				'description' => $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue,
				'author' => $def->getElementsByTagName( 'author' )->item( 0 )->nodeValue,
				'authorUrl' => $def->getElementsByTagName( 'authorUrl' )->item( 0 )->nodeValue,
				'authorMail' => $def->getElementsByTagName( 'authorEmail' )->item( 0 )->nodeValue,
				'enabled' => 1, 'type' => $type, 'depend' => null
			)
		);
	}

	protected function installLanguage( $def, $dir )
	{
		$this->checkRequirements( $def );
		$this->definition = new DOMDocument();
		$this->definition->formatOutput = true;
		$this->definition->preserveWhiteSpace = false;
		$this->definition->appendChild( $this->definition->createElement( 'SobiProApp' ) );
		$Install = $this->definition->createElement( 'installLog' );
		$Files = $this->definition->createElement( 'files' );
		$filesLog = array();
		$this->id = $def->getElementsByTagName( 'tag' )->item( 0 )->nodeValue;

		if ( $def->getElementsByTagName( 'administration' )->length ) {
			$this->langFiles( 'administration', $def, $dir, $filesLog );
		}
		if ( $def->getElementsByTagName( 'site' )->length ) {
			$this->langFiles( 'site', $def, $dir, $filesLog );
		}
		$this->storeData( 'language', $def );
		$dir = SPLoader::dirPath( 'etc.installed.languages', 'front', false );
		if ( !( SPFs::exists( $dir ) ) ) {
			SPFs::mkdir( $dir );
		}
		foreach ( $filesLog as $file ) {
			$Files->appendChild( $this->definition->createElement( 'file', $file ) );
		}

		$Install->appendChild( $Files );
		$root = $this->definition->getElementsByTagName( 'SobiProApp' )->item( 0 );
		$root->appendChild( $this->definition->createElement( 'id', $this->id ) );
		$root->appendChild( $this->definition->createElement( 'type', 'language' ) );
		$root->appendChild( $this->definition->createElement( 'name', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) );
		$root->appendChild( $Install );
		$this->definition->appendChild( $root );
		$path = "{$dir}/{$this->id}.xml";
		$file = SPFactory::Instance( 'base.fs.file', $path );
		$this->definition->normalizeDocument();
		$file->content( $this->definition->saveXML() );
		$file->save();
		if ( !( $this->error ) ) {
			return array( 'msg' => Sobi::Txt( 'LANG_INSTALLED', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ), 'msgtype' => SPC::SUCCESS_MSG );
		}
		else {
			return array( 'msg' => Sobi::Txt( 'LANG_INSTALLED', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) . "\n" . $this->error, 'msgtype' => $this->errorType );
		}
	}

	private function langFiles( $tag, $def, $dir, &$FilesLog )
	{
		$target = ( $tag == 'administration' ) ?
				implode( DS, array( SOBI_ROOT, 'administrator', 'language', $this->id ) ) :
				implode( DS, array( SOBI_ROOT, 'language', $this->id ) );
		if ( !( file_exists( $target ) ) ) {
			$this->error = Sobi::Txt( 'LANG_INSTALL_NO_CORE', $this->id );
			$this->errorType = SPC::WARN_MSG;
			SPFs::mkdir( $target );
		}
		$files = $def
				->getElementsByTagName( $tag )
				->item( 0 )
				->getElementsByTagName( 'files' )
				->item( 0 );
		$folder = $files->getAttribute( 'folder' );
		$folder = $dir . $folder . DS;
		foreach ( $files->getElementsByTagName( 'filename' ) as $file ) {
			if ( ( file_exists( $folder . $file->nodeValue ) ) ) {
				if ( !( SPFs::copy( $folder . $file->nodeValue, $target . DS . $file->nodeValue ) ) ) {
					SPFactory::message()->error( Sobi::Txt( 'Cannot copy %s to %s', $folder . $file->nodeValue, $target . DS . $file->nodeValue ), false );
				}
				else {
					$FilesLog[ ] = str_replace( array( DS . DS, SOBI_ROOT ), array( DS, null ), $target . DS . $file->nodeValue );
				}
			}
			else {
				SPFactory::message()->error( Sobi::Txt( 'File %s does not exist!', $folder . $file->nodeValue ), false );
			}
		}
	}

	/**
	 * @param $def
	 */
	protected function checkRequirements( $def )
	{
		$xp = new DOMXPath( $def );
		$requirements = $xp->query( '//SobiPro/requirements/*' );
		if ( $requirements && ( $requirements instanceof DOMNodeList ) ) {
			$reqCheck =& SPFactory::Instance( 'services.installers.requirements' );
			$reqCheck->check( $requirements );
		}
	}
}
