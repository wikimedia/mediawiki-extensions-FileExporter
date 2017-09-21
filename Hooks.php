<?php

namespace FileExporter;

use Message;
use SkinTemplate;
use WikiFilePage;
use MediaWiki\MediaWikiServices;
use User;
use BetaFeatures;

/**
 * @author Addshore
 */
class FileExporterHooks {

	public static function onSkinTemplateNavigation( SkinTemplate &$sktemplate, array &$links ) {
		global $wgUser;
		global $wgFileExporterTarget;

		$config = MediaWikiServices::getInstance()->getMainConfig();

		/**
		* If this extension is configured to be a beta feature, and the BetaFeatures extension
		* is loaded then require the current user to have the feature enabled.
		*/
		if (
			$config->get( 'FileExporterBetaFeature' ) &&
			class_exists( BetaFeatures::class ) &&
			!BetaFeatures::isFeatureEnabled( $wgUser, 'fileexporter' )
		) {
			return;
		}

		$title = $sktemplate->getTitle();
		$page = new WikiFilePage( $title );

		if (
			$title->getNamespace() !== NS_FILE ||
			!$page->isLocal() ||
			$sktemplate->getUser()->isNewbie()
		) {
			return;
		}

		$parsedUrl = wfParseUrl( $wgFileExporterTarget );
		$currentUrl = $sktemplate->getTitle()->getFullURL();

		if ( array_key_exists( 'query', $parsedUrl ) ) {
			$parsedUrl['query'] .= '&clientUrl=' . urlencode( $currentUrl );
		} else {
			$parsedUrl['query'] = 'clientUrl=' . urlencode( $currentUrl );
		}

		$targetUrl = wfAssembleUrl( $parsedUrl );

		$links['views']['fileExporter'] = [
			'class' => '',
			'text' => Message::newFromKey( 'fileexporter-text' )->plain(),
			'href' => $targetUrl,
			'target' => '_blank',
		];
	}

	public static function getBetaFeaturePreferences( User $user, array &$prefs ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$extensionAssetsPath = $config->get( 'ExtensionAssetsPath' );

		if ( $config->get( 'FileExporterBetaFeature' ) ) {
			$prefs[ 'fileexporter' ] = [
				'label-message' => 'fileexporter-beta-feature-message',
				'desc-message' => 'fileexporter-beta-feature-description',
				'screenshot' => "$extensionAssetsPath/FileExporter/resources/FileExporter-beta-features.svg",
				'info-link' => 'https://www.mediawiki.org/wiki/Extension:FileExporter',
				'discussion-link' => 'https://www.mediawiki.org/wiki/Extension_talk:FileExporter',
			];
		}
	}

}
