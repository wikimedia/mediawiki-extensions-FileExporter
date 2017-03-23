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

		$links['views']['fileExporter'] = [
			'class' => '',
			'text' => Message::newFromKey( 'fileexporter-text' )->plain(),
			'href' => $wgFileExporterTarget,
			'target' => '_blank',
		];
	}

}
