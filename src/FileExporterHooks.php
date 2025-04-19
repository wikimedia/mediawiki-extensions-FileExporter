<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace FileExporter;

use MediaWiki\ChangeTags\Hook\ChangeTagsAllowedAddHook;
use MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook;
use MediaWiki\ChangeTags\Hook\ListDefinedTagsHook;
use MediaWiki\Config\ConfigException;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Skin\SkinTemplate;
use MediaWiki\User\User;
use MediaWiki\Utils\UrlUtils;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class FileExporterHooks implements
	ChangeTagsAllowedAddHook,
	ChangeTagsListActiveHook,
	SkinTemplateNavigation__UniversalHook,
	ListDefinedTagsHook
{

	/**
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$links
	 */
	public function onSkinTemplateNavigation__Universal( $skinTemplate, &$links ): void {
		$context = $skinTemplate->getContext();
		$config = $context->getConfig();
		$user = $context->getUser();
		$title = $context->getTitle();

		if ( !$title ||
			!$title->inNamespace( NS_FILE ) ||
			!$title->exists() ||
			$user->isNewbie()
		) {
			return;
		}

		$services = MediaWikiServices::getInstance();
		$page = $services->getWikiPageFactory()->newFromTitle( $title );
		if ( !$page->isLocal() ) {
			return;
		}

		$target = $config->get( 'FileExporterTarget' );
		if ( !$target ) {
			throw new ConfigException( '$wgFileExporterTarget doesn\'t have a default, please set your own' );
		}
		$urlUtils = $services->getUrlUtils();
		$parsedUrl = $urlUtils->parse( (string)$target );
		$query = wfCgiToArray( $parsedUrl['query'] ?? '' );
		$query['clientUrl'] = $title->getFullURL( '', false, PROTO_CANONICAL );

		// Add another URL parameter in order to be able to track hits to the import special page
		// coming directly from the exporter.
		$query['importSource'] = 'FileExporter';

		$parsedUrl['query'] = wfArrayToCgi( $query );
		$targetUrl = UrlUtils::assemble( $parsedUrl );

		$links['views']['fileExporter'] = [
			'class' => '',
			'text' => self::getExportButtonLabel( $parsedUrl['host'] ),
			'href' => $targetUrl,
			'target' => '_blank',
		];
	}

	/**
	 * @param string $host
	 *
	 * @return string
	 */
	private static function getExportButtonLabel( $host ) {
		if ( $host === 'commons.wikimedia.org' ) {
			$msg = 'fileexporter-to-wikimedia-commons';
		} else {
			$msg = 'fileexporter-text';
		}

		return Message::newFromKey( $msg )->plain();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ListDefinedTags
	 *
	 * @param string[] &$tags
	 */
	public function onListDefinedTags( &$tags ) {
		$tags[] = 'fileimporter-remote';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangeTagsListActive
	 *
	 * @param string[] &$tags
	 */
	public function onChangeTagsListActive( &$tags ) {
		$tags[] = 'fileimporter-remote';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangeTagsAllowedAdd
	 * @param array &$allowedTags
	 * @param array $tags
	 * @param User|null $user
	 */
	public function onChangeTagsAllowedAdd( &$allowedTags, $tags, $user ) {
		$allowedTags[] = 'fileimporter-remote';
	}

}
