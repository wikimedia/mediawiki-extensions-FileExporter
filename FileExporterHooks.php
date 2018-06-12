<?php

namespace FileExporter;

use ExtensionRegistry;
use Message;
use SkinTemplate;
use WikiFilePage;
use MediaWiki\MediaWikiServices;
use User;
use BetaFeatures;

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
		global $wgUser;

		$config = MediaWikiServices::getInstance()->getMainConfig();

		/**
		* If this extension is configured to be a beta feature, and the BetaFeatures extension
		* is loaded then require the current user to have the feature enabled.
		*/
		if (
			$config->get( 'FileExporterBetaFeature' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) &&
			!BetaFeatures::isFeatureEnabled( $wgUser, 'fileexporter' )
		) {
			return;
		}

		$title = $skinTemplate->getTitle();
		$page = new WikiFilePage( $title );

		if (
			$title->getNamespace() !== NS_FILE ||
			!$page->isLocal() ||
			$skinTemplate->getUser()->isNewbie()
		) {
			return;
		}

		$parsedUrl = wfParseUrl( $config->get( 'FileExporterTarget' ) );
		$query = isset( $parsedUrl['query'] ) ? [ $parsedUrl['query'] ] : [];
		$query[] = 'clientUrl=' . urlencode( $skinTemplate->getTitle()->getFullURL() );

		// Add another URL parameter in order to be able to track hits to the import special page
		// coming directly from the exporter.
		$query[] = 'importSource=FileExporter';

		$parsedUrl['query'] = implode( '&', $query );
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
	 * @param User $user
	 * @param array[] &$prefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$prefs ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$extensionAssetsPath = $config->get( 'ExtensionAssetsPath' );

		if ( $config->get( 'FileExporterBetaFeature' )
			&& !$user->isNewbie()
		) {
			$prefs[ 'fileexporter' ] = [
				'label-message' => 'fileexporter-beta-feature-message',
				'desc-message' => 'fileexporter-beta-feature-description',
				'screenshot' => "$extensionAssetsPath/FileExporter/resources/FileExporter-beta-features.svg",
				'info-link' => 'https://www.mediawiki.org/wiki/Help:Extension:FileImporter',
				'discussion-link' => 'https://www.mediawiki.org/wiki/Help_talk:Extension:FileImporter',
			];
		}
	}

}
