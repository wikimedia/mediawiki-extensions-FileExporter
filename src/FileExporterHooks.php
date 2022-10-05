<?php

namespace FileExporter;

use MediaWiki\MediaWikiServices;
use Message;
use SkinTemplate;
use User;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class FileExporterHooks {

	/**
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$links
	 */
	public static function onSkinTemplateNavigation( SkinTemplate $skinTemplate, array &$links ) {
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

		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		if ( !$page->isLocal() ) {
			return;
		}

		$parsedUrl = wfParseUrl( $config->get( 'FileExporterTarget' ) );
		$query = wfCgiToArray( $parsedUrl['query'] ?? '' );
		$query['clientUrl'] = $title->getFullURL( '', false, PROTO_CANONICAL );

		// Add another URL parameter in order to be able to track hits to the import special page
		// coming directly from the exporter.
		$query['importSource'] = 'FileExporter';

		$parsedUrl['query'] = wfArrayToCgi( $query );
		$targetUrl = wfAssembleUrl( $parsedUrl );

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
		} elseif ( strpos( $host, '.beta.wmflabs.org' ) > 0
			|| preg_match( '/^test\d*\./i', $host )
		) {
			$msg = 'fileexporter-to-test';
		} else {
			$msg = 'fileexporter-text';
		}

		return Message::newFromKey( $msg )->plain();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangeTagsListActive
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ListDefinedTags
	 *
	 * @param string[] &$tags
	 */
	public static function onListDefinedTags( array &$tags ) {
		$tags[] = 'fileimporter-remote';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangeTagsAllowedAdd
	 * @param array &$allowedTags
	 * @param array $tags
	 * @param User|null $user
	 */
	public static function onChangeTagsAllowedAdd( array &$allowedTags, array $tags, User $user = null ) {
		$allowedTags[] = 'fileimporter-remote';
	}

}
