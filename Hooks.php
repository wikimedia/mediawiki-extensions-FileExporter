<?php

namespace FileExporter;

use Message;
use SkinTemplate;

/**
 * @author Addshore
 */
class FileExporterHooks {

	public static function onSkinTemplateNavigation( SkinTemplate &$sktemplate, array &$links ) {
		global $wgFileExporterTarget;

		if ( $sktemplate->getTitle()->getNamespace() !== NS_FILE ) {
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
