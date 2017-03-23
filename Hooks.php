<?php

namespace FileExporter;

use Message;
use SkinTemplate;
use WikiFilePage;

/**
 * @author Addshore
 */
class FileExporterHooks {

	public static function onSkinTemplateNavigation( SkinTemplate &$sktemplate, array &$links ) {
		global $wgFileExporterTarget;

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

}
